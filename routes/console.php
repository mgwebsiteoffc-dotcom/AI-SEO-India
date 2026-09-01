<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('visibility:track --all')->dailyAt('06:00');
