# StudentHub

A student portal built with plain **HTML, CSS, JavaScript, PHP and MySQL**. Students can browse and register for campus events, manage a profile and use their own dashboard. Administrators manage students and events and monitor activity from a dashboard with an audit log.

Built as the semester project for *Web Development Frameworks (ITUE203)* at CHARUSAT. No frameworks or build tools are needed: copy the folder into XAMPP, import one SQL file, and it runs.

## Features

**Public site**
- Home page with a featured-events slider, notice board and course cards
- Events page that loads `data/events.json` with search, category filter, sorting, pagination and a details popup
- FAQ with live search and collapsible answers (from `data/faqs.json`)
- Contact form (saved to a CSV file) and feedback form (saved to a JSON file), both validated in the browser and again on the server
- Dark and light themes that remember your choice, a responsive layout, a skip-to-content link and keyboard-friendly menus

**Students**
- Registration (validated in the browser and on the server, password strength meter, duplicate email/username check, `password_hash()`)
- Login and logout with PHP sessions, optional remember-me, 15-minute inactivity timeout
- Dashboard with their student ID, upcoming events and the events they registered for
- "Register for event" from the Events page
- Profile page (details and photo, kept in the browser)

**Administrators**
- Role-protected dashboard with live counts, latest registrations and the audit log
- Student management: add, edit, delete, search and filter
- Event management: add, edit, delete, poster upload with type and size checks
- Live event management using a JSON API and the Fetch API, with no page reloads
- Every admin change is written to the audit log

## Tech stack

| Layer | Used |
|---|---|
| Front end | HTML5, CSS3 (Grid and Flexbox), vanilla JavaScript (ES6+) |
| Data files | JSON (events, FAQs, student dataset), CSV |
| Back end | PHP 8.1+ |
| Database | MySQL / MariaDB (MySQLi for the app, PDO for the connection test) |
| Local server | XAMPP (Apache, MySQL), phpMyAdmin |

## Project structure

```text
StudentHub/
├── home.html  about.html  events.html  contact.html  faq.html  feedback.html  profile.html
├── login.php  register.php  student-dashboard.php  admin-dashboard.php
├── css/style.css                  all styles, light and dark themes
├── js/app.js                      all front-end behaviour
├── data/                          events.json, faqs.json, students.json
├── images/events/                 event posters (SVG)
├── database/studenthub.sql        schema + starter data (single file)
├── storage/                       contact and feedback submissions (created at runtime)
└── php/
    ├── config.php                 database settings and session timeout
    ├── mysqli.php  db.php         database connections (MySQLi / PDO)
    ├── layout.php                 shared header, menus and footer for PHP pages
    ├── auth.php                   sessions, timeout, remember-me, role and CSRF checks
    ├── authenticate.php           handles the login form
    ├── logout.php  me.php         logout; tells static pages who is signed in
    ├── process-contact.php  process-feedback.php
    ├── event-register.php         student registers for an event
    ├── admin-students.php  admin-events.php  admin-events-ajax.php  db-test.php
    ├── audit.php                  audit log helper
    ├── api/events.php             JSON API for events
    └── uploads/event-posters/     uploaded posters (script execution blocked)
```

## Getting started

**You need:** [XAMPP](https://www.apachefriends.org/) (PHP 8.1 or newer) and any modern browser.

1. Copy the `StudentHub` folder to `C:\xampp\htdocs\`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin/`, open the **Import** tab and import `database/studenthub.sql`. It creates the `studenthub` database, all tables and the starter data.
4. Open **http://localhost/StudentHub/home.html**.

Always open the site through `localhost`. Double-clicking an `.html` file will not load the JSON data, and PHP pages will not run.

If your MySQL uses a different user, password or database name, change it in `php/config.php`. That is the only place.

### Demo accounts

| Role | Email | Password |
|---|---|---|
| Admin | `admin@studenthub.edu` | `Admin@12345` |
| Student | `student@studenthub.edu` | `Student@12345` |

These are for local testing only. Change them (or delete the accounts) before putting the site anywhere public.

## How it fits together

- **Accounts.** Registering creates a row in `users` (login) and a row in `students` (profile data such as ID, course and year). If an admin already added a student with the same email, the new account is linked to that record instead.
- **Roles.** `requireRole('admin')` / `requireRole('student')` guard every protected page. Wrong role: access denied page. Not logged in: redirected to login.
- **Menus.** PHP pages build the menu on the server. Static pages ask `php/me.php` who is signed in and then show *Logout* and only the matching dashboard link. If PHP is not running, they keep the default menu.
- **Events.** The public Events and Home pages read `data/events.json`. The database holds the same 15 events (with the same IDs), which is what registrations and the admin pages use. If you add an event in the admin area, it is stored in the database; to show it on the public page as well, add it to `data/events.json` too.
- **CSRF.** Admin forms and the Fetch API send a session token that the server checks.

### JSON API (admin only)

`php/api/events.php`

| Method | Purpose | Body |
|---|---|---|
| `GET` (`?q=`) | List / search events | none |
| `POST` | Create an event | JSON event fields |
| `PUT` | Update an event | JSON event fields + `event_id` |
| `DELETE` | Delete an event | `{ "event_id": 1 }` |

Write requests need the header `X-CSRF-Token`. Responses are JSON with proper status codes (200, 201, 401, 403, 404, 405, 419, 422).

## Testing checklist

| # | Check | Expected |
|---|---|---|
| 1 | Open the home page and move through every menu link | Pages load, active item is highlighted |
| 2 | Switch theme, reload | Choice is remembered, no flash |
| 3 | Register with a weak password | Error shown next to the field |
| 4 | Register with valid details, then log in | Student dashboard opens |
| 5 | Register again with the same email | "Already registered" message |
| 6 | Log in with a wrong password | "Login failed" message |
| 7 | Open `admin-dashboard.php` as a student | Access denied |
| 8 | Log in as admin and add, edit, search and delete a student | Saved, shown, removed after confirmation |
| 9 | Add an event with a `.txt` file as poster | Rejected (JPG, PNG or WebP only, max 2 MB) |
| 10 | Use Live Events (AJAX) to add and delete an event | Table updates without reload |
| 11 | As a student, register for an event on the Events page | Confirmation, then it appears on the dashboard |
| 12 | Check the audit log on the admin dashboard | Your admin actions are listed |
| 13 | Leave a logged-in session idle for 15 minutes | Next page asks you to log in again |
| 14 | Submit the contact and feedback forms | Saved in `storage/` |
| 15 | Zoom the browser out to 25% and in to 200% | Layout stays centred and aligned |

## Security notes

Passwords are hashed with `password_hash()` and checked with `password_verify()`. Database access uses prepared statements. Sessions are regenerated on login and expire after 15 minutes of inactivity. Uploads are checked for real file type and size and renamed, and the upload folder blocks script execution. `storage/` is closed to direct web access.

This is a learning project. Before real deployment you would also want HTTPS, rate limiting on login, a non-root database user, and a security review.

## Troubleshooting

| Problem | Fix |
|---|---|
| "Database unavailable" page | Start MySQL, then import `database/studenthub.sql`. Check `php/config.php`. |
| PHP code shown as text | Open the page via `http://localhost/...`, with Apache running |
| Events or FAQ say "could not be loaded" | Open via `http://localhost`, not as a file |
| Login always fails | Re-import the SQL file; use the demo accounts above |
| Poster upload fails | Make sure `php/uploads/event-posters/` is writable |
| Menu shows both dashboards after login | `php/me.php` is not reachable; check Apache and the URL |
| Already imported an older version of this project | Drop the `studenthub` database in phpMyAdmin and import again (the `students` table gained a `gender` column) |

## Known limitations and ideas

- Attendance and results on the student dashboard are placeholders until a department publishes data; there are no tables for them yet.
- Public pages read JSON while admin pages use the database (see *Events* above). A public read-only API would unify them.
- Student pagination and CSV export, an active/inactive toggle for users, and an event-registrations chart would be natural next steps.

## Author

Vidhi Patel · Web Development Frameworks (ITUE203) · CHARUSAT