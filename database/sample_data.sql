-- BatchFund sample data. Run AFTER schema.sql.
-- Logins:  coordinator@batchfund.test / Coord@123   |   student1@batchfund.test … student8@batchfund.test / Student@123
USE batchfund;
SET NAMES utf8mb4;

INSERT INTO users (user_id, role, email, password , full_name) VALUES
(1,'coordinator','coordinator@batchfund.test','Coord@123','Batch Coordinator'),
(2,'student','254155J@batchfund.test','Thi26@12lina','Thilina Perera'),
(3,'student','254095A@batchfund.test','Nipul@123','Nipul Sanketh'),
(4,'student','student3@batchfund.test','$2y$10$uM3dy/qalHmy4loGFtvvbOHapp05IEdUR/4qWlCO94a9VV/6ThLh.','Dilshan Fernando'),
(5,'student','student4@batchfund.test','$2y$10$uM3dy/qalHmy4loGFtvvbOHapp05IEdUR/4qWlCO94a9VV/6ThLh.','Sachini Jayawardena'),
(6,'student','student5@batchfund.test','$2y$10$uM3dy/qalHmy4loGFtvvbOHapp05IEdUR/4qWlCO94a9VV/6ThLh.','Ruwan Bandara'),
(7,'student','student6@batchfund.test','$2y$10$uM3dy/qalHmy4loGFtvvbOHapp05IEdUR/4qWlCO94a9VV/6ThLh.','Tharushi Gunasekara'),
(8,'student','student7@batchfund.test','$2y$10$uM3dy/qalHmy4loGFtvvbOHapp05IEdUR/4qWlCO94a9VV/6ThLh.','Chamod Wickramasinghe'),
(9,'student','student8@batchfund.test','$2y$10$uM3dy/qalHmy4loGFtvvbOHapp05IEdUR/4qWlCO94a9VV/6ThLh.','Ishara Rajapaksha');

INSERT INTO students (student_id, user_id, reg_no, contact_no, district) VALUES
(1,2,'23/IT/001','0771110001','Colombo'),
(2,3,'23/IT/002','0771110002','Gampaha'),
(3,4,'23/IT/003','0771110003','Kandy'),
(4,5,'23/IT/004','0771110004','Galle'),
(5,6,'23/IT/005','0771110005','Kurunegala'),
(6,7,'23/IT/006','0771110006','Colombo'),
(7,8,'23/IT/007','0771110007','Matara'),
(8,9,'23/IT/008','0771110008','Gampaha');

INSERT INTO collections (collection_id, kind, title, description, amount, target_amount, collection_month, event_date, deadline, status, created_by) VALUES
(1,'monthly','September 2026 Batch Fund','Monthly batch fund contribution.',500,NULL,'2026-09-01',NULL,'2026-09-15','closed',1),
(2,'monthly','October 2026 Batch Fund','Monthly batch fund contribution.',500,NULL,'2026-10-01',NULL,'2026-10-15','open',1),
(3,'monthly','November 2026 Batch Fund','Monthly batch fund contribution.',500,NULL,'2026-11-01',NULL,'2026-11-15','open',1),
(4,'event','Batch T-Shirt 2026','Custom batch T-shirts for everyone.',1800,14400,NULL,'2026-12-01','2026-11-20','open',1),
(5,'event','Batch Trip – Ella','Two-day batch trip to Ella (transport + accommodation).',3500,28000,NULL,'2026-12-20','2026-12-05','open',1);

INSERT INTO payments (payment_id, student_id, collection_id, amount, reference_no, status, attempt_no, rejection_reason, submitted_at, reviewed_by, reviewed_at) VALUES
(1,1,1,500,'SEP-1001','approved',1,NULL,'2026-09-06 10:15:00',1,'2026-09-06 20:00:00'),
(2,2,1,500,'SEP-1002','approved',1,NULL,'2026-09-07 10:15:00',1,'2026-09-07 20:00:00'),
(3,3,1,500,'SEP-1003','approved',1,NULL,'2026-09-08 10:15:00',1,'2026-09-08 20:00:00'),
(4,4,1,500,'SEP-1004','approved',1,NULL,'2026-09-09 10:15:00',1,'2026-09-09 20:00:00'),
(5,5,1,500,'SEP-1005','approved',1,NULL,'2026-09-10 10:15:00',1,'2026-09-10 20:00:00'),
(6,6,1,500,'SEP-1006','approved',1,NULL,'2026-09-11 10:15:00',1,'2026-09-11 20:00:00'),
(7,7,1,500,'SEP-1007','approved',1,NULL,'2026-09-12 10:15:00',1,'2026-09-12 20:00:00'),
(8,8,1,500,'SEP-1008','approved',1,NULL,'2026-09-13 10:15:00',1,'2026-09-13 20:00:00'),
(9,1,2,500,'OCT-1009','approved',1,NULL,'2026-10-07 11:30:00',1,'2026-10-07 20:00:00'),
(10,2,2,500,'OCT-1010','approved',1,NULL,'2026-10-08 11:30:00',1,'2026-10-08 20:00:00'),
(11,3,2,500,'OCT-1011','approved',1,NULL,'2026-10-09 11:30:00',1,'2026-10-09 20:00:00'),
(12,4,2,500,'OCT-1012','approved',1,NULL,'2026-10-10 11:30:00',1,'2026-10-10 20:00:00'),
(13,5,2,500,'OCT-1013','approved',1,NULL,'2026-10-11 11:30:00',1,'2026-10-11 20:00:00'),
(14,6,2,500,'OCT-1014','under_verification',1,NULL,'2026-11-05 09:40:00',NULL,NULL),
(15,7,2,500,'OCT-1015','rejected',1,'Receipt image is blurred – please upload a clearer copy.','2026-10-14 16:05:00',1,'2026-10-14 20:00:00'),
(16,1,3,500,'NOV-1016','approved',1,NULL,'2026-11-03 08:20:00',1,'2026-11-03 20:00:00'),
(17,2,3,500,'NOV-1017','receipt_submitted',1,NULL,'2026-11-08 18:45:00',NULL,NULL),
(18,3,3,500,'NOV-1018','under_verification',1,NULL,'2026-11-07 13:10:00',NULL,NULL),
(19,1,4,1800,'TSH-1019','approved',1,NULL,'2026-11-02 12:00:00',1,'2026-11-02 20:00:00'),
(20,2,4,1800,'TSH-1020','approved',1,NULL,'2026-11-04 14:25:00',1,'2026-11-04 20:00:00'),
(21,4,4,1800,'TSH-1021','receipt_submitted',1,NULL,'2026-11-08 20:10:00',NULL,NULL),
(22,5,4,1800,'TSH-1022','rejected',1,'Transaction reference does not match the bank record.','2026-11-06 10:05:00',1,'2026-11-06 20:00:00');

INSERT INTO payment_receipts (payment_id, attempt_no, stored_name, original_name, mime_type, file_size) VALUES
(1,1,'sample_receipt.png','receipt_sep-1001.png','image/png',405),
(2,1,'sample_receipt.png','receipt_sep-1002.png','image/png',405),
(3,1,'sample_receipt.png','receipt_sep-1003.png','image/png',405),
(4,1,'sample_receipt.png','receipt_sep-1004.png','image/png',405),
(5,1,'sample_receipt.png','receipt_sep-1005.png','image/png',405),
(6,1,'sample_receipt.png','receipt_sep-1006.png','image/png',405),
(7,1,'sample_receipt.png','receipt_sep-1007.png','image/png',405),
(8,1,'sample_receipt.png','receipt_sep-1008.png','image/png',405),
(9,1,'sample_receipt.png','receipt_oct-1009.png','image/png',405),
(10,1,'sample_receipt.png','receipt_oct-1010.png','image/png',405),
(11,1,'sample_receipt.png','receipt_oct-1011.png','image/png',405),
(12,1,'sample_receipt.png','receipt_oct-1012.png','image/png',405),
(13,1,'sample_receipt.png','receipt_oct-1013.png','image/png',405),
(14,1,'sample_receipt.png','receipt_oct-1014.png','image/png',405),
(15,1,'sample_receipt.png','receipt_oct-1015.png','image/png',405),
(16,1,'sample_receipt.png','receipt_nov-1016.png','image/png',405),
(17,1,'sample_receipt.png','receipt_nov-1017.png','image/png',405),
(18,1,'sample_receipt.png','receipt_nov-1018.png','image/png',405),
(19,1,'sample_receipt.png','receipt_tsh-1019.png','image/png',405),
(20,1,'sample_receipt.png','receipt_tsh-1020.png','image/png',405),
(21,1,'sample_receipt.png','receipt_tsh-1021.png','image/png',405),
(22,1,'sample_receipt.png','receipt_tsh-1022.png','image/png',405);

INSERT INTO expenses (expense_id, title, description, category, amount, expense_date, collection_id, status, created_by, approved_by, approved_at) VALUES
(1,'Batch banner printing','Welcome banner for the batch orientation.','Printing & Stationery',1500,'2026-09-10',NULL,'approved',1,1,'2026-09-11 09:00:00'),
(2,'Orientation refreshments','Tea and snacks for the orientation session.','Food & Refreshments',1200,'2026-09-12',NULL,'approved',1,1,'2026-09-12 18:00:00'),
(3,'Welfare gift – Dilshan','Get-well gift basket.','Gifts & Welfare',800,'2026-10-05',NULL,'approved',1,1,'2026-10-05 17:30:00'),
(4,'T-shirt sample pieces','Sample shirts for size checking.','Event',1000,'2026-11-01',4,'approved',1,1,'2026-11-01 10:00:00'),
(5,'Stationery for committee','Markers, paper and folders.','Printing & Stationery',1800,'2026-11-07',NULL,'pending',1,NULL,NULL),
(6,'Duplicate entry (test)','Entered twice by mistake.','Other',700,'2026-10-20',NULL,'rejected',1,1,'2026-10-21 08:00:00');

INSERT INTO expense_documents (expense_id, stored_name, original_name, mime_type, file_size, is_public) VALUES (1,'sample_receipt.png','banner_invoice.png','image/png',405,1);

INSERT INTO announcements (title, body, status, publish_date, created_by) VALUES
('Welcome to BatchFund','All batch contributions are now managed online. Log in to view collections, pay and upload your receipts.','published','2026-09-01',1),
('Batch T-shirt order closing soon','Please pay for your T-shirt by 20 November so we can place the order. Sizes will be collected in class.','published','2026-11-05',1),
('Trip planning meeting (draft)','Planning meeting for the Ella trip – date to be confirmed.','draft','2026-11-10',1);

INSERT INTO notifications (user_id, type, message, is_read) VALUES
(2,'new_collection','New monthly collection: November 2026 Batch Fund – Rs. 500.00, due 15 Nov 2026.',1),
(2,'payment_approved','Your payment for "November 2026 Batch Fund" (Rs. 500.00) was approved.',0),
(2,'announcement','New announcement: Batch T-shirt order closing soon',0),
(8,'payment_rejected','Your payment for "Batch T-Shirt 2026" was rejected: Transaction reference does not match the bank record.',0),
(1,'payment_submitted','Kasuni Silva submitted a payment for "November 2026 Batch Fund".',0);

INSERT INTO email_logs (student_id, recipient_email, subject, collection_id, status, error_message, sent_by) VALUES
(8,'student8@batchfund.test','Overdue: October 2026 Batch Fund – BatchFund',2,'failed','Sample entry – SMTP not configured yet',1),
(7,'student7@batchfund.test','Reminder: November 2026 Batch Fund – BatchFund',3,'sent',NULL,1);

INSERT INTO audit_logs (user_id, action, entity, entity_id, details, ip_address) VALUES
(1,'approve_payment','payment',1,'Rs. 500.00 – September 2026 Batch Fund','127.0.0.1'),
(1,'add_expense','expense',1,'Batch banner printing Rs. 1,500.00 [approved]','127.0.0.1');
