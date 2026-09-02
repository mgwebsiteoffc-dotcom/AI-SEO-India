#!/usr/bin/env node
/**
 * Sandbox bootstrap helper: fetch composer packages straight from GitHub
 * (Packagist/getcomposer are blocked in this environment) and generate a
 * lightweight PSR-4 autoloader + Laravel package-discovery manifest.
 *
 * Usage: node scripts/fetch-vendor.mjs
 * Prereqs: GITHUB_TOKEN or GH_TOKEN (optional but avoids rate limits), unzip.
 */
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';

const root = process.cwd();
const lock = JSON.parse(fs.readFileSync(path.join(root, 'composer.lock'), 'utf8'));
const vendorDir = path.join(root, 'vendor');
const cacheDir = '/tmp/vendor-tgz';
fs.mkdirSync(vendorDir, { recursive: true });
fs.mkdirSync(cacheDir, { recursive: true });

const token = process.env.GITHUB_TOKEN || process.env.GH_TOKEN || '';
const authHeader = token ? ['-H', `Authorization: Bearer ${token}`] : [];

const packages = lock.packages; // runtime only (skip packages-dev)

async function curl(url, outFile) {
  const args = ['-sSL', '--retry', '2', '--max-time', '120', ...authHeader, '-o', outFile, url];
  execFileSync('curl', args);
}

function distUrl(p) {
  const d = p.dist || {};
  let u = d.url || '';
  if (!u && p.source) u = p.source.url || '';
  return u;
}

async function fetchOne(p) {
  const dest = path.join(vendorDir, p.name);
  if (fs.existsSync(path.join(dest, 'composer.json'))) return 'cached';
  const zip = path.join(cacheDir, p.name.replace(/\//g, '__') + '.zip');
  if (!fs.existsSync(zip)) {
    const u = distUrl(p);
    if (!u) throw new Error('no dist url for ' + p.name);
    await curl(u, zip);
  }
  const tmp = path.join(cacheDir, 'x-' + p.name.replace(/\//g, '__'));
  fs.rmSync(tmp, { recursive: true, force: true });
  fs.mkdirSync(tmp, { recursive: true });
  execFileSync('unzip', ['-q', '-o', zip, '-d', tmp]);
  // zipball contains one top-level "<repo>-<sha>/" dir
  const entries = fs.readdirSync(tmp).filter((e) => !e.startsWith('.'));
  const top = path.join(tmp, entries[0]);
  if (!fs.statSync(top).isDirectory()) throw new Error('bad zip layout for ' + p.name);
  fs.mkdirSync(dest, { recursive: true });
  for (const e of fs.readdirSync(top)) {
    fs.renameSync(path.join(top, e), path.join(dest, e));
  }
  fs.rmSync(tmp, { recursive: true, force: true });
  return 'fetched';
}

async function main() {
  const concurrency = 6;
  let i = 0;
  let fetched = 0, cached = 0;
  async function worker() {
    while (i < packages.length) {
      const p = packages[i++];
      const r = await fetchOne(p).catch((e) => {
        console.error('FAIL', p.name, e.message);
        return 'failed';
      });
      if (r === 'fetched') fetched++;
      if (r === 'cached') cached++;
      console.log(r.padEnd(8), p.name);
    }
  }
  await Promise.all(Array.from({ length: concurrency }, worker));
  console.log(`\ndone: ${fetched} fetched, ${cached} cached, ${packages.length - fetched - cached} failed`);

  // ---- generate autoload files ----
  generateAutoload();
  generateInstalledJson();
}

function generateAutoload() {
  const psr4 = {};   // prefix -> [dirs relative to vendorDir? absolute]
  const psr0 = {};
  const classmapDirs = [];
  const files = [];

  function readAutoload(pkgDir) {
    const f = path.join(vendorDir, pkgDir, 'composer.json');
    if (!fs.existsSync(f)) return {};
    try { return JSON.parse(fs.readFileSync(f, 'utf8')).autoload || {}; } catch { return {}; }
  }

  function pushTarget(map, ns, rel, baseDir) {
    const resolved = path.resolve(baseDir, rel);
    (map[ns] ??= []).push(resolved);
  }

  for (const p of packages) {
    const dir = p.name;
    const autoload = p.autoload || readAutoload(dir);
    const base = path.join(vendorDir, dir);
    for (const [ns, targets] of Object.entries(autoload['psr-4'] || {})) {
      for (const t of (Array.isArray(targets) ? targets : [targets])) pushTarget(psr4, ns, t, base);
    }
    for (const [ns, targets] of Object.entries(autoload['psr-0'] || {})) {
      for (const t of (Array.isArray(targets) ? targets : [targets])) pushTarget(psr0, ns, t, base);
    }
    for (const t of autoload['classmap'] || []) classmapDirs.push(path.resolve(base, t));
    for (const t of autoload['files'] || []) files.push(path.resolve(base, t));
  }

  // root project autoload (App\ etc.)
  const rootAutoload = JSON.parse(fs.readFileSync(path.join(root, 'composer.json'), 'utf8')).autoload || {};
  for (const [ns, targets] of Object.entries(rootAutoload['psr-4'] || {})) {
    for (const t of (Array.isArray(targets) ? targets : [targets])) pushTarget(psr4, ns, t, root);
  }
  for (const [ns, targets] of Object.entries(rootAutoload['psr-0'] || {})) {
    for (const t of (Array.isArray(targets) ? targets : [targets])) pushTarget(psr0, ns, t, root);
  }
  for (const t of rootAutoload['classmap'] || []) classmapDirs.push(path.resolve(root, t));

  const outDir = path.join(vendorDir, 'composer');
  fs.mkdirSync(outDir, { recursive: true });

  const phpify = (obj) => {
    const lines = Object.entries(obj).map(([k, v]) => {
      if (Array.isArray(v)) {
        const items = v.map((x) => `        ${JSON.stringify(x)}`).join(',\n');
        return `    ${JSON.stringify(k)} => [\n${items}\n    ],`;
      }
      return `    ${JSON.stringify(k)} => ${JSON.stringify(v)},`;
    });
    return `array (\n${lines.join('\n')}\n)`;
  };

  fs.writeFileSync(path.join(outDir, 'autoload_psr4.php'),
    `<?php\n\n// autoload_psr4.php @generated by scripts/fetch-vendor.mjs\n\nreturn ${phpify(psr4)};\n`);

  fs.writeFileSync(path.join(outDir, 'autoload_psr0.php'),
    `<?php\n\n// autoload_psr0.php @generated by scripts/fetch-vendor.mjs\n\nreturn ${phpify(psr0)};\n`);

  // classmap: scan directories for class/interface/trait declarations (best effort)
  const classMap = {};
  for (const dir of classmapDirs) {
    if (!fs.existsSync(dir)) continue;
    const walk = (d) => {
      for (const e of fs.readdirSync(d)) {
        const full = path.join(d, e);
        if (fs.statSync(full).isDirectory()) walk(full);
        else if (e.endsWith('.php')) {
          const code = fs.readFileSync(full, 'utf8');
          const fqcn = guessFqcn(code);
          if (fqcn) classMap[fqcn] = full;
        }
      }
    };
    walk(dir);
  }
  // add Laravel's own helper classmap entries if present
  fs.writeFileSync(path.join(outDir, 'autoload_classmap.php'),
    `<?php\n\n// autoload_classmap.php @generated by scripts/fetch-vendor.mjs\n\nreturn ${phpify(Object.fromEntries(Object.entries(classMap).map(([k, v]) => [k, v])))};\n`);

  fs.writeFileSync(path.join(outDir, 'autoload_files.php'),
    `<?php\n\n// autoload_files.php @generated by scripts/fetch-vendor.mjs\n\nreturn [\n${files.map((f, idx) => `    ${idx} => ${JSON.stringify(f)},`).join('\n')}\n];\n`);

  // autoload.php is a static template (kept in-repo so backslash escaping is never mangled)
  fs.copyFileSync(path.join(root, 'scripts', 'templates', 'autoload.php'), path.join(vendorDir, 'autoload.php'));

  console.log('autoload files generated:', Object.keys(psr4).length, 'psr-4 prefixes,', files.length, 'files');
}

function guessFqcn(code) {
  const ns = code.match(/namespace\s+([^;{}]+);/);
  const cls = code.match(/\b(?:class|interface|trait)\s+([A-Za-z_][A-Za-z0-9_]*)/);
  if (!cls) return null;
  const nsName = ns ? ns[1].trim() : '';
  return nsName ? nsName + '\\' + cls[1] : cls[1];
}

function generateInstalledJson() {
  const pkgs = packages.map((p) => {
    const out = {
      name: p.name,
      version: p.version,
      'version_normalized': p.version_normalized || p.version,
      source: p.source || null,
      dist: p.dist || null,
      require: p.require || {},
      'require-dev': p['require-dev'] || {},
      autoload: p.autoload || {},
      extra: p.extra || {},
      'install-path': '../' + p.name,
    };
    return out;
  });
  const data = { packages: pkgs, 'dev': false, 'dev-package-names': [] };
  fs.writeFileSync(path.join(vendorDir, 'composer', 'installed.json'), JSON.stringify(data, null, 2));
  console.log('installed.json written with', pkgs.length, 'packages');
}

main().catch((e) => { console.error(e); process.exit(1); });
