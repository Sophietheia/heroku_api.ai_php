DROP TABLE meetings;
DROP TABLE tasks;
DROP TABLE places;
DROP TABLE relations;
DROP TABLE doctors;
DROP TABLE users;

CREATE TABLE doctors(
	id 			serial primary key,
	name   		VARCHAR(30),
	surname  	VARCHAR(30),
	email  		VARCHAR(30),
	phone		INT
);

CREATE TABLE users(
	id 			serial primary key,
	name   		VARCHAR(30),
	email  		VARCHAR(30),
	surname  	VARCHAR(30),
	birthday  	DATE,
	address   	VARCHAR(40)
);

CREATE  TABLE relations(
	id 		serial primary key,
	name    VARCHAR(30),
	surname  VARCHAR(30),
	link_user  VARCHAR(30),
	id_user   INT,
	link    VARCHAR(30),
	link_photo VARCHAR(30)
);

CREATE TABLE meetings(
	id 				serial primary key,
	label 			VARCHAR(40),
	location 			VARCHAR(20),
	date_meeting 		DATE,
	time_meeting		TIME,
	id_user	INT,
	id_person   	INT,
	FOREIGN KEY(id_person) REFERENCES relations(id),
	FOREIGN KEY(id_user) REFERENCES users(id)
);

CREATE TABLE places(
	id 		serial primary key,
	name  VARCHAR(30),
	country  VARCHAR(30),
	id_user  INT,
	link_photo  VARCHAR(30),
	FOREIGN KEY(id_user) REFERENCES users(id)
);


INSERT INTO users(name, email, surname, birthday, address) VALUES('Muller', 'franck@gmail.com', 'Franck', '1995-09-04', '3 avenue des Champs Elysees');
