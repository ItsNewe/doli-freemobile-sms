# Free Mobile SMS for Dolibarr

SMS sending engine for [Dolibarr ERP & CRM](https://www.dolibarr.org) using the free
[Free Mobile notification API](https://smsapi.free-mobile.fr/sendmsg).

The API only texts the line that owns the login: this module is for notifications to your own Free Mobile lines
(agenda reminders, tests, any feature of Dolibarr that sends SMS).

## Features

- Registers as an SMS sending method, selectable in **Setup > SMS**
- Several lines: each recipient number is matched to its own login and key
- Keys stored encrypted, never displayed again
- Template around every message (header / footer), with `__SMS_MESSAGE__` and the standard substitution variables

## Install

1. Build the zip with `./build.sh` (or download it from the releases) and deploy it in
   **Setup > Modules > Deploy external module**. The module folder is `freemobilesms`.
2. Enable **Free Mobile SMS**.
3. In your Free Mobile subscriber area, enable **Notifications par SMS** (Mes Options) and note the login and key.
4. In the module setup, add each line (number, login, key) and set the template.
5. In **Setup > SMS**, choose `freemobilesms` as sending method and send a test SMS.

## Requirements

Dolibarr 20+, PHP 7.2+, PHP curl.

## License

GPLv3 or later, see [COPYING](COPYING).
