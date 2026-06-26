-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : ven. 26 juin 2026 à 14:32
-- Version du serveur : 8.4.7
-- Version de PHP : 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gestion_immobiliere`
--

-- --------------------------------------------------------

--
-- Structure de la table `affectations`
--

DROP TABLE IF EXISTS `affectations`;
CREATE TABLE IF NOT EXISTS `affectations` (
  `id_affectation` int NOT NULL AUTO_INCREMENT,
  `id_client` int NOT NULL,
  `id_agent` int NOT NULL,
  `date_affectation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_affectation`),
  UNIQUE KEY `unique_client` (`id_client`),
  KEY `id_agent` (`id_agent`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `affectations`
--

INSERT INTO `affectations` (`id_affectation`, `id_client`, `id_agent`, `date_affectation`) VALUES
(1, 6, 2, '2026-06-25 06:38:51'),
(2, 7, 3, '2026-06-25 06:38:58');

-- --------------------------------------------------------

--
-- Structure de la table `demandes_visite`
--

DROP TABLE IF EXISTS `demandes_visite`;
CREATE TABLE IF NOT EXISTS `demandes_visite` (
  `id_visite` int NOT NULL AUTO_INCREMENT,
  `id_client` int NOT NULL,
  `id_propriete` int NOT NULL,
  `id_agent` int DEFAULT NULL,
  `message_client` text COLLATE utf8mb4_unicode_ci,
  `date_souhaitee` date DEFAULT NULL,
  `statut` enum('attente','validee','refusee') COLLATE utf8mb4_unicode_ci DEFAULT 'attente',
  `date_demande` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `message_agent` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_visite`),
  KEY `id_client` (`id_client`),
  KEY `id_propriete` (`id_propriete`),
  KEY `id_agent` (`id_agent`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `demandes_visite`
--

INSERT INTO `demandes_visite` (`id_visite`, `id_client`, `id_propriete`, `id_agent`, `message_client`, `date_souhaitee`, `statut`, `date_demande`, `message_agent`) VALUES
(1, 6, 2, 2, 'J\'aimerais visiter cette Propriété', '2026-06-30', 'validee', '2026-06-25 06:44:27', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `documents`
--

DROP TABLE IF EXISTS `documents`;
CREATE TABLE IF NOT EXISTS `documents` (
  `id_document` int NOT NULL AUTO_INCREMENT,
  `id_propriete` int NOT NULL,
  `nom_original` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type_doc` enum('attestation','titre_foncier','permis','autre') COLLATE utf8mb4_unicode_ci NOT NULL,
  `chemin_doc` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `acces` enum('prive','public') COLLATE utf8mb4_unicode_ci DEFAULT 'prive',
  PRIMARY KEY (`id_document`),
  KEY `id_propriete` (`id_propriete`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `documents`
--

INSERT INTO `documents` (`id_document`, `id_propriete`, `nom_original`, `type_doc`, `chemin_doc`, `acces`) VALUES
(1, 1, 'titre_foncier.pdf', 'autre', 'uploads/documents/doc_1_6a3cbecc058e0.pdf', 'prive'),
(2, 2, 'Villa_titre_foncier.pdf', 'autre', 'uploads/documents/doc_2_6a3cc44eaa8df.pdf', 'prive'),
(3, 3, 'Villa R+1_titre_foncier.pdf', 'autre', 'uploads/documents/doc_3_6a3cc7d797cf1.pdf', 'prive'),
(4, 4, 'VILLAS_titre_foncier.pdf', 'autre', 'uploads/documents/doc_4_6a3e7b266f454.pdf', 'prive'),
(5, 5, 'VILLAR+2_titre_foncier.pdf', 'autre', 'uploads/documents/doc_5_6a3e88a410ab6.pdf', 'prive');

-- --------------------------------------------------------

--
-- Structure de la table `favoris`
--

DROP TABLE IF EXISTS `favoris`;
CREATE TABLE IF NOT EXISTS `favoris` (
  `id_favori` int NOT NULL AUTO_INCREMENT,
  `id_client` int NOT NULL,
  `id_propriete` int NOT NULL,
  `date_ajout` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_favori`),
  UNIQUE KEY `unique_favori` (`id_client`,`id_propriete`),
  KEY `id_propriete` (`id_propriete`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `id_message` int NOT NULL AUTO_INCREMENT,
  `id_expediteur` int NOT NULL,
  `id_destinataire` int NOT NULL,
  `contenu` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `lu` tinyint(1) DEFAULT '0',
  `date_envoi` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_message`),
  KEY `id_expediteur` (`id_expediteur`),
  KEY `id_destinataire` (`id_destinataire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id_notification` int NOT NULL AUTO_INCREMENT,
  `destinataire` enum('manager') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manager',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_propriete` int DEFAULT NULL,
  `lu` tinyint(1) DEFAULT '0',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_notification`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `photos`
--

DROP TABLE IF EXISTS `photos`;
CREATE TABLE IF NOT EXISTS `photos` (
  `id_photo` int NOT NULL AUTO_INCREMENT,
  `id_propriete` int NOT NULL,
  `chemin_photo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `est_principale` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id_photo`),
  KEY `id_propriete` (`id_propriete`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `photos`
--

INSERT INTO `photos` (`id_photo`, `id_propriete`, `chemin_photo`, `est_principale`) VALUES
(1, 1, 'uploads/photos/prop_6a3cbecc0856b.png', 1),
(2, 2, 'uploads/photos/prop_6a3cc44eac652.png', 1),
(3, 3, 'uploads/photos/prop_6a3cc7d7987e3.png', 1),
(4, 4, 'uploads/photos/prop_6a3e7b2671aac.png', 1),
(5, 5, 'uploads/photos/prop_6a3e88a412b97.png', 1);

-- --------------------------------------------------------

--
-- Structure de la table `proprietes`
--

DROP TABLE IF EXISTS `proprietes`;
CREATE TABLE IF NOT EXISTS `proprietes` (
  `id_propriete` int NOT NULL AUTO_INCREMENT,
  `id_bailleur` int NOT NULL,
  `id_agent` int DEFAULT NULL,
  `titre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_bien` enum('villa','appartement','r_plus_1','r_plus_2','r_plus_3','terrain','commerce','batiment') COLLATE utf8mb4_unicode_ci NOT NULL,
  `modele` enum('vente','location') COLLATE utf8mb4_unicode_ci NOT NULL,
  `zone` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `superficie` int DEFAULT NULL,
  `prix` decimal(12,2) NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('attente','affectee','publiee','refusee','retiree') COLLATE utf8mb4_unicode_ci DEFAULT 'attente',
  `commentaire` text COLLATE utf8mb4_unicode_ci,
  `date_depot` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_propriete`),
  KEY `id_bailleur` (`id_bailleur`),
  KEY `id_agent` (`id_agent`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `proprietes`
--

INSERT INTO `proprietes` (`id_propriete`, `id_bailleur`, `id_agent`, `titre`, `type_bien`, `modele`, `zone`, `superficie`, `prix`, `description`, `image_url`, `statut`, `commentaire`, `date_depot`) VALUES
(1, 5, 2, 'Terrain', 'terrain', 'vente', 'Sandogo', NULL, 20000000.00, 'Terrain résidentiel de choix à Sandogo – 20 Millions\r\n\r\nVous recherchez le lieu idéal pour bâtir votre futur foyer ou réaliser un investissement sûr ? Découvrez ce magnifique terrain situé à Sandogo, une zone en plein essor qui offre un cadre de vie paisible tout en restant accessible.\r\n\r\nLes points forts de ce bien :\r\n\r\nEmplacement stratégique : Zone calme, sécurisée et en plein développement.\r\n\r\nAccessibilité : Terrain facilement accessible, proche des voies principales.\r\n\r\nViabilisation : [Précisez si électricité/eau disponibles].\r\n\r\nDocuments : [Mentionnez le type de document : Attestation de possession foncière / Titre foncier].\r\n\r\nPrix : 20 000 000 FCFA (Net vendeur).', NULL, 'publiee', '', '2026-06-25 05:38:20'),
(2, 5, 2, 'Belle Villa', 'r_plus_1', 'location', 'Samba', NULL, 300000.00, 'Belle Villa R+1 à louer à Samba – 600 m²\r\n\r\nDécouvrez cette magnifique villa R+1 située dans le quartier résidentiel de Samba, offrant un cadre de vie agréable, sécurisé et confortable. Construite sur une superficie de 600 m², cette propriété spacieuse est idéale pour une famille, une résidence de fonction ou toute personne recherchant un logement de standing.\r\n\r\nLa villa dispose de vastes espaces de vie lumineux, de chambres confortables, d\'une cuisine fonctionnelle ainsi que de sanitaires modernes. Son grand espace extérieur permet l\'aménagement d\'un jardin, d\'une aire de détente ou d\'un parking pour plusieurs véhicules.\r\n\r\nSituée dans un environnement calme et facilement accessible, cette villa bénéficie de la proximité des commerces, écoles, centres de santé et autres services essentiels.\r\n\r\nCaractéristiques :\r\n\r\nType de bien : Villa R+1\r\nLocalisation : Samba\r\nSuperficie : 600 m²\r\nOption : Location\r\nLoyer mensuel : 300 000 FCFA\r\nQuartier calme et sécurisé\r\nGrand espace extérieur\r\nAccès facile aux commodités\r\n\r\nNe manquez pas cette opportunité de louer une villa spacieuse et confortable dans l\'un des secteurs recherchés de Samba. Contactez-nous dès maintenant pour plus d\'informations ou pour organiser une visite.', NULL, 'publiee', '', '2026-06-25 06:01:50'),
(3, 4, NULL, 'Villa R+1', 'r_plus_1', 'location', 'Kaya', NULL, 2750000.00, 'Villa R+1 moderne à louer à Kaya – 400 m²\r\n\r\nDécouvrez cette superbe villa R+1 située dans un quartier calme et accessible de Kaya. Construite sur une superficie de 400 m², cette propriété offre un cadre de vie confortable, sécurisé et adapté aux besoins d\'une famille ou d\'une entreprise recherchant un logement spacieux.\r\n\r\nLa villa dispose de grands espaces de vie lumineux, de chambres confortables, d\'une cuisine fonctionnelle ainsi que de sanitaires modernes. Son architecture R+1 permet une excellente répartition des pièces et garantit un confort optimal au quotidien.\r\n\r\nL\'espace extérieur offre suffisamment de place pour le stationnement de plusieurs véhicules ainsi que pour l\'aménagement d\'un jardin ou d\'un espace de détente. La propriété bénéficie également d\'un accès facile aux commerces, établissements scolaires, centres de santé et autres commodités essentielles.\r\n\r\nCaractéristiques principales\r\nType de bien : Villa R+1\r\nLocalisation : Kaya\r\nSuperficie : 400 m²\r\nOption : Location\r\nLoyer mensuel : 2 750 000 FCFA\r\nQuartier calme et sécurisé\r\nGrand espace de stationnement\r\nProximité des services et infrastructures\r\n\r\nCette villa constitue une excellente opportunité pour toute personne recherchant un logement de standing dans un environnement agréable. Contactez-nous dès maintenant pour obtenir plus d\'informations ou planifier une visite.', NULL, 'publiee', NULL, '2026-06-25 06:16:55'),
(4, 4, NULL, 'Villa', 'villa', 'vente', 'Tanghin', NULL, 55000000.00, 'Magnifique villa de standing à vendre à Tanghin – 400 m²\r\n\r\nOffrez-vous cette superbe villa située dans le quartier recherché de Tanghin, construite sur une superficie de 400 m². Cette propriété allie confort, élégance et fonctionnalité, idéale pour une famille souhaitant vivre dans un environnement calme, sécurisé et facilement accessible.\r\n\r\nLa villa comprend de vastes espaces de vie lumineux, plusieurs chambres spacieuses, une cuisine moderne et fonctionnelle, des salles d\'eau bien aménagées ainsi qu\'une grande cour permettant le stationnement de plusieurs véhicules. Son architecture soignée et ses finitions de qualité en font un bien prêt à être habité.\r\n\r\nSituée à proximité des écoles, des commerces, des centres de santé et des principaux axes routiers, cette villa offre un excellent cadre de vie tout en garantissant un investissement immobilier durable.\r\n\r\nCaractéristiques du bien\r\nType de bien : Villa\r\nOption : Vente\r\nLocalisation : Tanghin\r\nSuperficie : 400 m²\r\nPrix de vente : 55 000 000 FCFA\r\nVilla spacieuse et bien entretenue\r\nGrande cour avec espace de stationnement\r\nQuartier résidentiel calme et sécurisé\r\nAccès facile aux commodités essentielles\r\nIdéale pour une résidence principale ou un investissement immobilier', NULL, 'publiee', NULL, '2026-06-26 13:14:14'),
(5, 5, 2, 'Villa R+2', 'r_plus_2', 'vente', 'Ouaga 2000', NULL, 150000000.00, 'Magnifique Villa R+2 de haut standing à vendre à Ouaga 2000 – 600 m²\r\n\r\nDécouvrez cette somptueuse villa R+2 située dans le prestigieux quartier de Ouaga 2000, reconnue pour son environnement résidentiel, sécurisé et son excellente accessibilité. Construite sur une superficie de 600 m², cette propriété offre un cadre de vie exceptionnel alliant confort, modernité et élégance.\r\n\r\nLa villa dispose de vastes salons lumineux, de plusieurs chambres spacieuses avec salles d\'eau, d\'une cuisine moderne entièrement aménagée, d\'une salle à manger, de balcons offrant une belle vue ainsi que de nombreux espaces de rangement. Son architecture contemporaine et ses finitions de qualité supérieure en font une résidence idéale pour une famille ou un investissement immobilier de prestige.\r\n\r\nÀ l\'extérieur, la propriété comprend une grande cour entièrement aménagée, un jardin paysager, un espace de stationnement pouvant accueillir plusieurs véhicules ainsi qu\'une dépendance pour le personnel. Située à proximité des administrations, écoles internationales, centres commerciaux, restaurants et établissements de santé, cette villa bénéficie d\'un emplacement privilégié.\r\n\r\nCaractéristiques principales\r\nType de bien : Villa R+2\r\nOption : Vente\r\nLocalisation : Ouaga 2000\r\nSuperficie : 600 m²\r\nPrix de vente : 150 000 000 FCFA\r\nArchitecture moderne avec finitions haut de gamme\r\nGrandes pièces lumineuses et bien ventilées\r\nGrand jardin et vaste espace de stationnement\r\nQuartier résidentiel calme, sécurisé et facilement accessible\r\nProximité immédiate des principales commodités\r\n\r\nCette villa représente une opportunité rare pour les acquéreurs à la recherche d\'un bien immobilier de prestige à Ouaga 2000. Contactez-nous dès maintenant pour obtenir davantage d\'informations ou organiser une visite.', NULL, 'publiee', '', '2026-06-26 14:11:48');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id_utilisateur` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mot_de_passe` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('client','bailleur','agent','manager') COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `id_agent` int DEFAULT NULL,
  PRIMARY KEY (`id_utilisateur`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_client_agent` (`id_agent`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id_utilisateur`, `nom`, `prenom`, `email`, `mot_de_passe`, `role`, `telephone`, `date_creation`, `id_agent`) VALUES
(1, 'SOUGUE', 'Charly', 'manager@gmail.com', '$2y$10$x0F/6sAI0sJxdHExVqeodOIdvceCVnMY12nvT/D0qMs6bIl282COK', 'manager', '+22607592243', '2026-06-24 12:06:25', NULL),
(2, 'DAO', 'Ali', 'dao@gmail.com', '$2y$10$G5.wMZFTfmjLGhyD.q8CYOQFonPqfCGxBQdteTECtJ6P9NU/tSPZO', 'agent', '+22671797530', '2026-06-24 21:09:20', NULL),
(3, 'OUEDRAOGO', 'Paco', 'paco@gmail.com', '$2y$10$nZtX27APdQbecf.y5km45etwSH7G5WKvg0KUp7UYwsdQ.cXGyJN9K', 'agent', '+22655039192', '2026-06-24 21:11:17', NULL),
(4, 'OUEDRAOGO', 'Razack', 'razack@gmail.com', '$2y$10$vnY3ahX8RoB9xpraqHHqs.C6otIr144g.CD1xBqarRPgDBN2SVJp.', 'bailleur', '+22605343970', '2026-06-24 21:13:17', NULL),
(5, 'OUEDRAOGO', 'Archad', 'archad@gmail.com', '$2y$10$LnMHBHALCZIlEs1Kuyo/fu4bm089wKLlSPDV2/keBjguKGTZPMh.6', 'bailleur', '+22656904805', '2026-06-24 21:14:12', NULL),
(6, 'ZEBA', 'Ben', 'ben@gmail.com', '$2y$10$sS1QjInBavrH9ke6kbQYeeZl0y2SDTMTE80itdVkaKbO64/edDpnS', 'client', '+22662993876', '2026-06-24 21:14:44', 2),
(7, 'TRAORE', 'pierre', 'pierre@gmail.com', '$2y$10$/ArFYb5ivRFVKcxesZoVturbPVq3n.a6p.4tsjzillim8C1gXfZkO', 'client', '+22608075645', '2026-06-24 21:15:08', 3);

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `affectations`
--
ALTER TABLE `affectations`
  ADD CONSTRAINT `affectations_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `affectations_ibfk_2` FOREIGN KEY (`id_agent`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `demandes_visite`
--
ALTER TABLE `demandes_visite`
  ADD CONSTRAINT `demandes_visite_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `demandes_visite_ibfk_2` FOREIGN KEY (`id_propriete`) REFERENCES `proprietes` (`id_propriete`) ON DELETE CASCADE,
  ADD CONSTRAINT `demandes_visite_ibfk_3` FOREIGN KEY (`id_agent`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE SET NULL;

--
-- Contraintes pour la table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`id_propriete`) REFERENCES `proprietes` (`id_propriete`) ON DELETE CASCADE;

--
-- Contraintes pour la table `favoris`
--
ALTER TABLE `favoris`
  ADD CONSTRAINT `favoris_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `favoris_ibfk_2` FOREIGN KEY (`id_propriete`) REFERENCES `proprietes` (`id_propriete`) ON DELETE CASCADE;

--
-- Contraintes pour la table `photos`
--
ALTER TABLE `photos`
  ADD CONSTRAINT `photos_ibfk_1` FOREIGN KEY (`id_propriete`) REFERENCES `proprietes` (`id_propriete`) ON DELETE CASCADE;

--
-- Contraintes pour la table `proprietes`
--
ALTER TABLE `proprietes`
  ADD CONSTRAINT `proprietes_ibfk_1` FOREIGN KEY (`id_bailleur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `proprietes_ibfk_2` FOREIGN KEY (`id_agent`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE SET NULL;

--
-- Contraintes pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD CONSTRAINT `fk_client_agent` FOREIGN KEY (`id_agent`) REFERENCES `utilisateurs` (`id_utilisateur`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
