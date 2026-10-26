-- StudentHub database: schema and starter data.
-- Import once into an empty "studenthub" database (or let this file create it).
-- It is safe to re-run: tables use IF NOT EXISTS and starter rows use INSERT IGNORE.

CREATE DATABASE IF NOT EXISTS studenthub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE studenthub;

-- Login accounts -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('student','admin') NOT NULL DEFAULT 'student',
  remember_token_hash CHAR(64) NULL,
  remember_expires DATETIME NULL,
  last_login DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Student records ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS students (
  student_id VARCHAR(10) PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  mobile VARCHAR(10),
  course ENUM('B.Tech IT','B.Tech CE','B.Tech CSE') NOT NULL,
  year ENUM('1st Year','2nd Year','3rd Year','4th Year') NOT NULL,
  gender ENUM('Female','Male','Other') NULL,
  status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Events ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS events (
  event_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  category VARCHAR(50) NOT NULL,
  event_date DATE NOT NULL,
  event_time VARCHAR(30) NOT NULL,
  venue VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  image VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Event registrations (one per student per event) ------------------------------
CREATE TABLE IF NOT EXISTS registrations (
  registration_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id VARCHAR(10) NOT NULL,
  event_id INT UNSIGNED NOT NULL,
  registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_registration_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_registration_event FOREIGN KEY (event_id) REFERENCES events(event_id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT uq_student_event UNIQUE (student_id, event_id)
) ENGINE=InnoDB;

-- Audit log of admin actions -------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_logs (
  audit_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(80) NOT NULL,
  entity_type VARCHAR(40) NOT NULL,
  entity_id VARCHAR(80) NULL,
  details VARCHAR(255) NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_created_at (created_at),
  INDEX idx_audit_user_id (user_id),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- Starter data -----------------------------------------------------------------
-- Demo accounts (change these passwords before any real use):
--   admin@studenthub.edu    / Admin@12345
--   student@studenthub.edu  / Student@12345
INSERT IGNORE INTO users (name, username, email, password_hash, role) VALUES
('StudentHub Admin','admin','admin@studenthub.edu','$2y$12$4eaVofBhyybZ17NVxw1xsuntKzLiSqQEdGemKAfVc2EWmAd.2GGmO','admin'),
('Demo Student','student','student@studenthub.edu','$2y$12$pRe0WjBKgj881BgXijkbGe1K.UrylZYFkwnwhEKqCk9hOd57mDcrS','student');

INSERT IGNORE INTO students (student_id, name, email, mobile, course, year) VALUES
('STU001','Aarav Shah','aaravshah@studenthub.edu',NULL,'B.Tech IT','3rd Year'),
('STU002','Diya Patel','diyapatel@studenthub.edu',NULL,'B.Tech IT','3rd Year'),
('STU003','Rohan Mehta','rohanmehta@studenthub.edu',NULL,'B.Tech CE','3rd Year'),
('STU004','Mahi Desai','mahidesai@studenthub.edu',NULL,'B.Tech IT','2nd Year'),
('STU005','Kunal Joshi','kunaljoshi@studenthub.edu',NULL,'B.Tech CE','3rd Year'),
('STU006','Nisha Trivedi','nishatrivedi@studenthub.edu','9876500006','B.Tech IT','3rd Year'),
('STU007','Dev Patel','devpatel@studenthub.edu','9876500007','B.Tech CSE','2nd Year'),
('STU008','Anaya Shah','anayashah@studenthub.edu','9876500008','B.Tech CE','1st Year'),
('STU009','Yash Mehta','yashmehta@studenthub.edu','9876500009','B.Tech IT','4th Year'),
('STU010','Isha Joshi','ishajoshi@studenthub.edu','9876500010','B.Tech CSE','3rd Year'),
('STU011','Krish Desai','krishdesai@studenthub.edu','9876500011','B.Tech CE','2nd Year'),
('STU012','Myra Shah','myrashah@studenthub.edu','9876500012','B.Tech IT','1st Year'),
('STU013','Arjun Patel','arjunpatel@studenthub.edu','9876500013','B.Tech CSE','4th Year'),
('STU014','Sara Mehta','saramehta@studenthub.edu','9876500014','B.Tech CE','3rd Year'),
('STU015','Reyansh Joshi','reyanshjoshi@studenthub.edu','9876500015','B.Tech IT','2nd Year'),
('STU016','Aadhya Desai','aadhyadesai@studenthub.edu','9876500016','B.Tech CSE','1st Year'),
('STU017','Vivaan Shah','vivaanshah@studenthub.edu','9876500017','B.Tech CE','4th Year'),
('STU018','Pari Patel','paripatel@studenthub.edu','9876500018','B.Tech IT','3rd Year'),
('STU019','Advik Mehta','advikmehta@studenthub.edu','9876500019','B.Tech CSE','2nd Year'),
('STU020','Kiara Joshi','kiarajoshi@studenthub.edu','9876500020','B.Tech CE','1st Year'),
('STU021','Demo Student','student@studenthub.edu','9876500021','B.Tech IT','3rd Year');

INSERT IGNORE INTO events (event_id, title, category, event_date, event_time, venue, description, image) VALUES
(1,'Tech Fest 2026','Technical','2026-10-12','10:00 AM','Main Auditorium','A student technology festival featuring project showcases, coding activities and technical competitions.','images/events/event-01.png'),
(2,'Student Meetup','Community','2026-10-18','11:00 AM','Seminar Hall','A student gathering to connect with peers, exchange ideas and discover campus communities.','images/events/event-02.png'),
(3,'Cultural Night','Cultural','2026-10-24','05:00 PM','Campus Ground','An evening of music, dance and student performances from across the campus.','images/events/event-03.png'),
(4,'Web Development Workshop','Technical','2026-09-29','10:00 AM','Computer Lab 2','A practical session covering HTML, CSS and JavaScript through small web activities.','images/events/event-04.png'),
(5,'Career Preparation Session','Career','2026-10-02','11:00 AM','Placement Cell','A focused session on resumes, interviews and preparation for software development roles.','images/events/event-05.png'),
(6,'Coding Challenge','Technical','2026-10-07','02:00 PM','Programming Lab','A programming challenge with problem-solving activities for students.','images/events/event-06.png'),
(7,'Photography Walk','Creative','2026-10-09','04:30 PM','Campus Gate','A guided campus photography activity for students interested in visual storytelling.','images/events/event-07.png'),
(8,'Sports Day','Sports','2026-10-15','08:00 AM','Sports Ground','Inter-class sports activities and team events for students.','images/events/event-08.png'),
(9,'AI Awareness Talk','Technical','2026-10-20','01:00 PM','Seminar Hall','An introductory talk about current uses of artificial intelligence in technology and education.','images/events/event-09.png'),
(10,'Design Showcase','Creative','2026-10-22','03:00 PM','Design Studio','Students present interface designs, creative work and digital projects.','images/events/event-10.png'),
(11,'Innovation Meetup','Innovation','2026-10-27','11:00 AM','Innovation Lab','A student meetup for discussing ideas, prototypes and practical problem-solving.','images/events/event-11.png'),
(12,'Open Mic Evening','Cultural','2026-10-30','05:30 PM','Student Activity Centre','An open stage for music, poetry, storytelling and student performances.','images/events/event-12.png'),
(13,'Cyber Safety Session','Technical','2026-11-03','12:00 PM','Computer Lab 1','A student-focused session on safe passwords, phishing awareness and responsible online practices.','images/events/event-13.png'),
(14,'Project Expo','Innovation','2026-11-07','10:00 AM','Main Auditorium','A showcase of student projects, prototypes and practical work from different courses.','images/events/event-14.png'),
(15,'Student Wellness Workshop','Community','2026-11-12','02:00 PM','Seminar Hall','A practical workshop focused on healthy study routines, time planning and student wellbeing.','images/events/event-15.png');

INSERT IGNORE INTO registrations (student_id, event_id, registered_at) VALUES
('STU003',1,'2026-09-20 10:15:00'),('STU002',2,'2026-09-21 11:20:00'),('STU005',5,'2026-09-22 14:05:00');
