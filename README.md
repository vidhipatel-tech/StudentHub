# StudentHub
A web-based student portal designed for managing events, student profiles, feedback, and dashboard views for both students and administrators.
---

## 📁 Directory Structure

StudentHub/
│
├── 📄 home.html               # Main landing page
├── 📄 about.html              # About page
├── 📄 contact.html            # Contact us page
├── 📄 login.html              # User authentication login page
├── 📄 register.html           # User account registration page
├── 📄 profile.html            # Student profile management
├── 📄 student-dashboard.html  # Student interface & metrics
├── 📄 admin-dashboard.html    # Admin management panel
├── 📄 events.html             # Event listings and details
├── 📄 faq.html                # Frequently Asked Questions page
├── 📄 feedback.html           # Feedback submission page
│
├── 📁 css/
│   └── style.css              # Main application stylesheet
│
├── 📁 js/
│   └── app.js                 # Primary JavaScript logic & interaction dynamics
│
├── 📁 data/
│   ├── events.json            # Event dataset
│   ├── faqs.json              # FAQ dataset
│   └── students.json          # Mock student profiles & records
│
└── 📁 images/
    └── events/                # Event vectors and thumbnails

✨ Features
Authentication System: UI structures for student registration and login.
Dashboards: Dedicated views for students (student-dashboard.html) and system administrators (admin-dashboard.html).
Event Portal: Comprehensive event directory powered by SVG graphics (images/events/) and dynamic data rendering (events.json).
Interactive UI & Data Binding: Frontend logic in js/app.js handles data loading from JSON files (data/).
Support & Feedback: Features dynamic FAQs and a direct feedback portal.

🛠️ Technology Stack
Frontend: HTML5, CSS3, JavaScript (Vanilla JS)
Data Source: JSON (data/ directory)
Assets: SVG vector assets for events

🚀 Getting Started
Clone or Extract: Ensure all files maintain the folder structure listed above.

Run Locally:
Open home.html in any modern web browser.

Note: Because the site loads JSON files via asynchronous requests (fetch), it is recommended to run the project through a local development server (such as VS Code Live Server or python -m http.server) to avoid CORS restrictions on local file paths (file://).

📝 Lab Work Scope
This project demonstrates key front-end development milestones including responsive layout structure, modular styling, client-side scripting, JSON data processing, and dashboard layout design.
