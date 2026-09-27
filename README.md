# StudentHub — Practical 1 to 6

StudentHub is a semester project for Web Development Frameworks (ITUE203). This version covers the project work through Practical 6 using HTML5, CSS3, JavaScript and JSON.

## Pages
- Home
- About
- Events
- Contact
- FAQ
- Feedback
- Register
- Login
- Student Dashboard
- Profile
- Admin Dashboard

## Branches
StudentHub uses three B.Tech branches only:
- B.Tech IT
- B.Tech CE
- B.Tech CSE

## Practical coverage
- Practical 1: project structure, page planning, role areas and navigation
- Practical 2: semantic HTML5 structure, labels, headings, links and image alt text
- Practical 3: responsive CSS Grid, Flexbox, media queries and reusable styling
- Practical 4: collapsible FAQ, modal popup, event slider, notification banner, hamburger menu, theme switcher and localStorage
- Practical 5: registration validation, regular expressions, password strength, confirm password and user-friendly errors
- Practical 6: events.json, students.json and faqs.json with Fetch API, rendering, search, filter, sort and pagination

## Image handling
Event posters are stored locally in `images/events/` and their paths are supplied through `data/events.json`. Profile photos are selected from the user's device, shown inside a fixed-size preview box with `object-fit: cover`, limited to 2 MB, and saved in browser localStorage for the profile page.

## Run
Open the project through a local web server so Fetch API can load the JSON files correctly.
