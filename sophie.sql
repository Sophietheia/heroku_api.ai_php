CREATE TABLE medecins(
	id 		INT,
	nom VARCHAR(40),
	prenom VARCHAR(30),
	email  VARCHAR(30),
	telephone INT NOT NULL,
	PRIMARY KEY(id)
);
 
CREATE TABLE utilisateurs(
	id 		INT,
	nom   VARCHAR(30),
	email  VARCHAR(30),
	prenom  VARCHAR(30),
	date_de_naissance  DATE,
	adresse   VARCHAR(40),
	PRIMARY KEY(id)
);

CREATE  TABLE entourage(
	id 		INT,
	nom    VARCHAR(30),
	prenom  VARCHAR(30),
	lien_utilisateur  VARCHAR(30),
	id_utilisateur   INT,
	lien    VARCHAR(30),
	lien_photo VARCHAR(30),
	PRIMARY KEY(id),
	FOREIGN KEY(id_utilisateur) REFERENCES utilisateurs(id)
);

CREATE TABLE rdv(
	id 				INT,
	label 			VARCHAR(40),
	lieu 			VARCHAR(20),
	date_rdv 		DATE,
	time_rdv		TIME,
	id_personne   	INT,
	PRIMARY KEY(id),
	FOREIGN KEY(id_personne) REFERENCES entourage(id)
);
 
CREATE TABLE lieux(
	id 		INT,
	nom  VARCHAR(30),
	pays  VARCHAR(30),
	id_utilisateur  INT,
	lien_photo  VARCHAR(30),
	PRIMARY KEY(id),
	FOREIGN KEY(id_utilisateur) REFERENCES utilisateurs(id)
);
 
CREATE  TABLE taches(
	id 		INT,
	nom    VARCHAR(30),
	description  VARCHAR(30),
	date_taches    DATE,
	id_utilisateur   INT,
	PRIMARY KEY(id),
	FOREIGN KEY(id_utilisateur) REFERENCES utilisateurs(id)
);
