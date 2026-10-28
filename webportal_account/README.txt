WEBPORTAL_ACCOUNT - ACCOUNT RECORD KEEPING / SELLING PORTAL  (PHP + PDO + MySQL)

SETUP (XAMPP)
1. Copy this folder to C:\xampp\htdocs\webportal_account
2. Start Apache and MySQL in the XAMPP control panel.
3. phpMyAdmin > Import > database/schema.sql > Go.
   (Already imported the OLD schema? Run database/upgrade.sql instead, or drop the
    account_portal database and import schema.sql again.)
4. config/config.php: keep BASE_URL as /webportal_account and change APP_KEY.
5. Open http://localhost/webportal_account/  (you are sent to login.php until you log in)

USER ENTITY
  users: first_name, last_name, age, address, contact_number, email, username, password_hash,
         added_by, updated_by, created_at, last_updated
  accounts: ... added_by, updated_by, last_updated
  activity_log: who inserted / updated / deleted what, and when (also records deletions)

PAGES
  login.php / register.php / logout.php
  index.php          homepage (login required)
  members.php        PARENT table: all users, added by, last updated by, last updated
  member_form.php    INSERT a member (added_by = logged-in user)
  profile.php        UPDATE your own information, CLOSE ACCOUNT (delete the user)
  seller/records.php CHILDREN table: insert, update, delete, publish, mark sold
  market/            buyer view
  activity.php       log of every insert / update / delete with the user

ONE-TO-MANY
  users (PARENT) 1 --- * accounts (CHILDREN)
