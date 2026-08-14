-- Référentiel de base pour MySQL

INSERT INTO niveaux_formation (id, nom) VALUES
    (UUID(), 'BTS 1ère année'), (UUID(), 'BTS 2ème année'),
    (UUID(), 'Licence 1'), (UUID(), 'Licence 2'), (UUID(), 'Licence 3'),
    (UUID(), 'Master 1'), (UUID(), 'Master 2');

INSERT INTO domaines_formation (id, nom) VALUES
    ('d0000000-0000-0000-0000-000000000001', 'Informatique'),
    ('d0000000-0000-0000-0000-000000000002', 'Marketing & Communication'),
    ('d0000000-0000-0000-0000-000000000003', 'Gestion & Comptabilite');

INSERT INTO metiers (id, domaine_formation_id, nom) VALUES
    ('m0000000-0000-0000-0000-000000000001', 'd0000000-0000-0000-0000-000000000001', 'Developpeur web'),
    ('m0000000-0000-0000-0000-000000000002', 'd0000000-0000-0000-0000-000000000001', 'Administrateur systemes et reseaux'),
    ('m0000000-0000-0000-0000-000000000003', 'd0000000-0000-0000-0000-000000000001', 'Data analyst'),
    ('m0000000-0000-0000-0000-000000000004', 'd0000000-0000-0000-0000-000000000002', 'Charge de marketing digital'),
    ('m0000000-0000-0000-0000-000000000005', 'd0000000-0000-0000-0000-000000000002', 'Community manager'),
    ('m0000000-0000-0000-0000-000000000006', 'd0000000-0000-0000-0000-000000000003', 'Assistant comptable'),
    ('m0000000-0000-0000-0000-000000000007', 'd0000000-0000-0000-0000-000000000003', 'Gestionnaire des ressources humaines');


INSERT INTO competences (id, metier_id, nom, description, seuil_decouverte, seuil_maitrise, mots_cles_detection) VALUES
    (UUID(), 'm0000000-0000-0000-0000-000000000001', 'Redaction de code', 'Ecriture et maintenance de code applicatif', 10, 40, JSON_ARRAY('code','developpement','programmation')),
    (UUID(), 'm0000000-0000-0000-0000-000000000001', 'Integration front-end', 'Mise en page et interactivite web', 8, 30, JSON_ARRAY('html','css','frontend')),
    (UUID(), 'm0000000-0000-0000-0000-000000000001', 'Travail en equipe agile', 'Participation aux rituels agiles', 5, 20, JSON_ARRAY('sprint','stand-up','agile')),
    (UUID(), 'm0000000-0000-0000-0000-000000000004', 'Gestion de campagnes publicitaires', 'Conception et suivi de campagnes en ligne', 10, 40, JSON_ARRAY('publicite','campagne','ads')),
    (UUID(), 'm0000000-0000-0000-0000-000000000004', 'SEO / Referencement', 'Optimisation du referencement naturel', 8, 30, JSON_ARRAY('seo','referencement')),
    (UUID(), 'm0000000-0000-0000-0000-000000000006', 'Saisie comptable', 'Enregistrement des ecritures comptables', 10, 40, JSON_ARRAY('saisie','comptabilite')),
    (UUID(), 'm0000000-0000-0000-0000-000000000006', 'Facturation', 'Emission et suivi des factures', 6, 25, JSON_ARRAY('facture','facturation'));

INSERT INTO criteres_savoir_etre (id, nom, description) VALUES
    (UUID(), 'Autonomie', 'Capacite a travailler sans supervision constante'),
    (UUID(), 'Communication', 'Clarte et pertinence dans les echanges'),
    (UUID(), 'Esprit d equipe', 'Collaboration et entraide avec les collegues'),
    (UUID(), 'Fiabilite', 'Respect des engagements et des delais');
