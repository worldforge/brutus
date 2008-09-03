CREATE TABLE messages (id INTEGER PRIMARY KEY,stamp REAL,channel TEXT,user TEXT,content TEXT,rating INTEGER);
CREATE TABLE meta (id INTEGER PRIMARY KEY,message INTEGER,owner INTEGER, data TEXT);
CREATE TABLE searches (id INTEGER PRIMARY KEY,user INTEGER,data TEXT,lastrun REAL,runcount INTEGER,active INTEGER);
CREATE TABLE user (id INTEGER PRIMARY KEY,user TEXT,email TEXT,password TEXT,reference INTEGER,settings TEXT,confirmed INTEGER);


-- messages are created from each log item in every file.
-- messages can be rated on wherever they appear. (N)
-- when a message is rated, the surrounding 5 lines are rated with a diminished rating value. (N-1)
-- meta can be attached to a message containing a string.
-- meta appears loaded via ajax when the meta key is invoked.
-- user records are created for every unique nickname discovered in the system.
-- user records may be claimed by invoking the claim process.
-- the claim process sends an email to an account specified in a form field as well as another user who has already been validated.
-- both users must click the link that appears in the email which links back to the site to validate.
-- when the requesting user clicks her link; she will have the opportunity to enter a password.
-- passwords are stored for later authentication.
-- settings can be stored for the user such as: allow emails (via form submit), remember searches?, stored searches?
