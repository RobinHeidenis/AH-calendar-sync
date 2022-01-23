# Running the project

Of course you need a PHP interpreter.

## Setup

1. Clone the project
2. Rename `consts.example.php` to `consts.php`
3. Fill in the consts
4. Rename `status.example.json` to `status.json` and fill in the password field (this should be your AH SAM password)

## Running

Run `getdata_ah.php` to generate `appointments_ah.json` in the `scheduledata` folder. This is a json file containing all
the dates and times you have to work

Point your calendar application to `google_getcalendar_ah.php`. This file generates an ICS file that can be read by most
calendar applications.