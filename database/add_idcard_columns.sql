-- Run this SQL if duplication_reason / joining_letter columns are missing
-- The model uses employee_i_d_card_requests (Laravel default for EmployeeIDCardRequest)
-- Run each line separately; ignore "Duplicate column" errors if column exists

ALTER TABLE employee_i_d_card_requests ADD COLUMN joining_letter VARCHAR(255) NULL AFTER photo;
ALTER TABLE employee_i_d_card_requests ADD COLUMN duplication_reason VARCHAR(255) NULL AFTER request_for;
