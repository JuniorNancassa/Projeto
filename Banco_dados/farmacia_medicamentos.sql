-- MySQL dump 10.13  Distrib 8.0.40, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: farmacia
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `medicamentos`
--

DROP TABLE IF EXISTS `medicamentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `medicamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) DEFAULT NULL,
  `descricao` varchar(250) DEFAULT NULL,
  `quantidade` int(11) DEFAULT NULL,
  `preco` decimal(10,2) DEFAULT NULL,
  `validade` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `medicamentos`
--

LOCK TABLES `medicamentos` WRITE;
/*!40000 ALTER TABLE `medicamentos` DISABLE KEYS */;
INSERT INTO `medicamentos` VALUES (1,'Budutur ','Medicamento para dor de barriga.',64,1500.00,'2027-02-09'),(2,'Novalgina','Diminuidor da temperatura corporal',2,5000.00,'0000-00-00'),(3,'Aspirina ','Fervente e calmante de dor da cabeça',2,4500.00,'0000-00-00'),(4,'Complexo B','Para boquera',2,200.00,'0000-00-00'),(5,'Ampola','Para injeção de dor',5,1500.00,'0000-00-00'),(8,'Tegretol 200 mg','Cada comprimido contém 200 mg de carbamazepin',4,15000.00,'2026-02-09'),(10,'Diclofenac 100mg','Medicamento para dor articulares',11,1000.00,'2025-02-18'),(11,'Diclofenac 100mg','Medicamento para dor articulares',18,1000.00,'2025-02-18'),(12,'Gaviscou','Medicamento para dor de estomago',6,15000.00,'0000-00-00'),(13,'Canafistra','medicamento para dor de corpo.',5,2500.00,'0000-00-00'),(14,'Canafistra','medicamento para dor de corpo.',5,2500.00,'0000-00-00'),(15,'Canafistra','medicamento para dor de corpo.',5,2500.00,'0000-00-00'),(16,'Canafistra','medicamento para dor de corpo.',5,2500.00,'2025-03-24'),(17,'Canafistra','medicamento para dor de corpo.',5,2500.00,'2025-03-24'),(18,'Vitamina C 100 mg','medicamento para aumentar apetite ',220,6000.00,'2027-02-02'),(19,'Quinina 100mg','medicamento para cefaleia',44,2500.00,'2027-02-02'),(20,'Madronha','Medicamento para acalmar a dor de barrica',81,8000.00,'2028-11-09');
/*!40000 ALTER TABLE `medicamentos` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-06-05 12:56:32
