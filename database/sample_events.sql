-- Insert sample events
INSERT INTO events (title, date, location, description, category, capacity) VALUES
-- Tech Events
('Web Development Workshop', DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 7 DAY), 'Tech Hub, Room 101', 'Learn modern web development techniques with hands-on exercises. Perfect for beginners and intermediate developers.', 'Technology', 30),
('AI & Machine Learning Conference', DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 14 DAY), 'Innovation Center', 'Explore the latest trends in AI and machine learning with industry experts.', 'Technology', 100),
('Cybersecurity Seminar', DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 21 DAY), 'Virtual Event', 'Understanding modern cybersecurity threats and protection strategies.', 'Technology', 50),

-- Business Events
('Entrepreneurship Summit', DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 5 DAY), 'Business Center', 'Connect with successful entrepreneurs and learn from their experiences.', 'Business', 75),
('Digital Marketing Masterclass', DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 10 DAY), 'Marketing Hub', 'Master the art of digital marketing with practical strategies and case studies.', 'Business', 40),

-- Arts & Culture
('Photography Exhibition', DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 3 DAY), 'Art Gallery', 'Showcasing works from emerging photographers around the world.', 'Arts', 120),
('Creative Writing Workshop', DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 8 DAY), 'Library Hall', 'Develop your writing skills with guidance from published authors.', 'Arts', 25),

-- Health & Wellness
('Yoga and Meditation Retreat', DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 15 DAY), 'Wellness Center', 'A day of relaxation and mindfulness with certified instructors.', 'Health', 30),
('Nutrition Workshop', DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 6 DAY), 'Health Center', 'Learn about balanced nutrition and meal planning for a healthy lifestyle.', 'Health', 45),

-- Education
('IELTS Preparation Course', DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 12 DAY), 'Education Center', 'Intensive IELTS preparation with mock tests and personalized feedback.', 'Education', 35),
('Science Fair 2024', DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 25 DAY), 'Convention Center', 'Annual science fair showcasing innovative projects from students.', 'Education', 200); 