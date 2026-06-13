<?php
/**
 * Application Constants
 */

// User roles
define('ROLE_ADMIN', 'admin');
define('ROLE_LECTURER', 'lecturer');
define('ROLE_STUDENT', 'student');
define('ROLE_FINANCE', 'finance');
define('ROLE_ADMISSIONS', 'admissions');

// User status
define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');
define('STATUS_SUSPENDED', 'suspended');
define('STATUS_PENDING', 'pending');

// Payment status
define('PAYMENT_PENDING', 'pending');
define('PAYMENT_COMPLETED', 'completed');
define('PAYMENT_FAILED', 'failed');
define('PAYMENT_CANCELLED', 'cancelled');

// Academic status
define('STATUS_PASS', 'pass');
define('STATUS_FAIL', 'fail');
define('STATUS_DISTINCTION', 'distinction');
define('STATUS_INCOMPLETE', 'incomplete');

// Grade scale
define('GRADE_A_PLUS', 'A+');
define('GRADE_A', 'A');
define('GRADE_B_PLUS', 'B+');
define('GRADE_B', 'B');
define('GRADE_C_PLUS', 'C+');
define('GRADE_C', 'C');
define('GRADE_D', 'D');
define('GRADE_F', 'F');
define('GRADE_INCOMPLETE', 'I');
define('GRADE_WITHDRAWN', 'W');

// Error messages
define('ERR_INVALID_CREDENTIALS', 'Invalid username or password');
define('ERR_ACCOUNT_LOCKED', 'Account is locked');
define('ERR_ACCOUNT_SUSPENDED', 'Account has been suspended');
define('ERR_SESSION_EXPIRED', 'Session has expired. Please login again');
define('ERR_UNAUTHORIZED', 'You do not have permission to access this resource');
define('ERR_NOT_FOUND', 'Resource not found');
define('ERR_DATABASE', 'Database error occurred');
define('ERR_INVALID_INPUT', 'Invalid input provided');
define('ERR_INSUFFICIENT_FUNDS', 'Insufficient payment');

// Success messages
define('SUCCESS_LOGIN', 'Login successful');
define('SUCCESS_LOGOUT', 'Logout successful');
define('SUCCESS_REGISTRATION', 'Registration successful');
define('SUCCESS_UPDATE', 'Update successful');
define('SUCCESS_DELETE', 'Delete successful');
define('SUCCESS_PAYMENT', 'Payment recorded successfully');

// HTTP status codes
define('HTTP_OK', 200);
define('HTTP_CREATED', 201);
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_SERVER_ERROR', 500);

// Date formats
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'd/m/Y');
define('DISPLAY_DATETIME_FORMAT', 'd/m/Y H:i');

// Pagination
define('ITEMS_PER_PAGE', 20);
define('MAX_PAGE_LINKS', 10);

// Session
define('SESSION_NAME', 'IDMA_SMS_LMS');
define('SESSION_COOKIE_LIFETIME', 1800);

?>
