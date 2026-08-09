-- PC Builder sample data for MySQL / MariaDB
-- Generated for realistic hardware catalog seeding

CREATE TABLE IF NOT EXISTS `cpus` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `brand` VARCHAR(50) NOT NULL,
  `socket` VARCHAR(50) NOT NULL,
  `cores` INT NOT NULL,
  `threads` INT NOT NULL,
  `base_clock` VARCHAR(20),
  `boost_clock` VARCHAR(20),
  `tdp_watts` INT NOT NULL,
  `has_integrated_gpu` TINYINT(1) DEFAULT 0,
  `price_vnd` BIGINT NOT NULL,
  `release_year` INT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `rams` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `brand` VARCHAR(50) NOT NULL,
  `ram_type` VARCHAR(20) NOT NULL,
  `capacity_gb` INT NOT NULL,
  `bus_speed` INT NOT NULL,
  `sticks_count` INT DEFAULT 1,
  `price_vnd` BIGINT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `mainboards` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `brand` VARCHAR(50) NOT NULL,
  `chipset` VARCHAR(50) NOT NULL,
  `socket` VARCHAR(50) NOT NULL,
  `form_factor` VARCHAR(20) NOT NULL,
  `ram_type` VARCHAR(20) NOT NULL,
  `ram_slots` INT DEFAULT 4,
  `price_vnd` BIGINT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `vgas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `brand` VARCHAR(50) NOT NULL,
  `chipset_manufacturer` VARCHAR(50) NOT NULL,
  `vram_gb` INT NOT NULL,
  `vram_type` VARCHAR(20),
  `recommended_psu_watts` INT NOT NULL,
  `length_mm` INT,
  `price_vnd` BIGINT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `psus` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `brand` VARCHAR(50) NOT NULL,
  `wattage` INT NOT NULL,
  `efficiency_rating` VARCHAR(50),
  `modular_type` VARCHAR(50),
  `form_factor` VARCHAR(20) DEFAULT 'ATX',
  `price_vnd` BIGINT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cases` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `brand` VARCHAR(50) NOT NULL,
  `supported_mainboards` VARCHAR(100) NOT NULL,
  `max_vga_length_mm` INT NOT NULL,
  `side_panel` VARCHAR(50),
  `price_vnd` BIGINT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `cpus` (`name`,`brand`,`socket`,`cores`,`threads`,`base_clock`,`boost_clock`,`tdp_watts`,`has_integrated_gpu`,`price_vnd`,`release_year`) VALUES
('Intel Core i3-12100F','Intel','LGA1700',4,8,'3.3 GHz','4.3 GHz',58,0,1850000,2022),
('Intel Core i3-13100','Intel','LGA1700',4,8,'3.4 GHz','4.5 GHz',60,1,2450000,2023),
('Intel Core i5-12400F','Intel','LGA1700',6,12,'2.5 GHz','4.4 GHz',65,0,3200000,2022),
('Intel Core i5-13400F','Intel','LGA1700',10,16,'2.5 GHz','4.6 GHz',65,0,4350000,2023),
('Intel Core i5-14400F','Intel','LGA1700',10,16,'2.5 GHz','4.7 GHz',65,0,4950000,2024),
('Intel Core i7-12700F','Intel','LGA1700',12,20,'2.1 GHz','4.9 GHz',65,0,6250000,2022),
('Intel Core i7-13700F','Intel','LGA1700',16,24,'2.1 GHz','5.2 GHz',65,0,8400000,2023),
('Intel Core i7-14700K','Intel','LGA1700',20,28,'3.4 GHz','5.6 GHz',125,1,11200000,2024),
('Intel Core i9-13900K','Intel','LGA1700',24,32,'3.0 GHz','5.8 GHz',125,1,13800000,2022),
('Intel Core i9-14900K','Intel','LGA1700',24,32,'3.2 GHz','6.0 GHz',125,1,15500000,2023),
('AMD Ryzen 5 5500','AMD','AM4',6,12,'3.6 GHz','4.2 GHz',65,0,2100000,2022),
('AMD Ryzen 5 5600','AMD','AM4',6,12,'3.5 GHz','4.4 GHz',65,0,2500000,2022),
('AMD Ryzen 5 5600X','AMD','AM4',6,12,'3.7 GHz','4.6 GHz',65,0,3050000,2020),
('AMD Ryzen 7 5700X','AMD','AM4',8,16,'3.4 GHz','4.6 GHz',65,0,4050000,2022),
('AMD Ryzen 7 5800X3D','AMD','AM4',8,16,'3.4 GHz','4.5 GHz',105,0,7500000,2022),
('AMD Ryzen 5 7500F','AMD','AM5',6,12,'3.7 GHz','5.0 GHz',65,0,4250000,2023),
('AMD Ryzen 5 7600','AMD','AM5',6,12,'3.8 GHz','5.1 GHz',65,1,5200000,2023),
('AMD Ryzen 7 7700','AMD','AM5',8,16,'3.8 GHz','5.3 GHz',65,1,7600000,2023),
('AMD Ryzen 7 7800X3D','AMD','AM5',8,16,'4.2 GHz','5.0 GHz',120,0,10200000,2023),
('AMD Ryzen 9 7900','AMD','AM5',12,24,'3.7 GHz','5.4 GHz',65,1,11800000,2023),
('AMD Ryzen 9 7950X','AMD','AM5',16,32,'4.5 GHz','5.7 GHz',170,1,14900000,2022),
('AMD Ryzen 9 9950X','AMD','AM5',16,32,'4.3 GHz','5.7 GHz',170,1,16900000,2024);

INSERT INTO `rams` (`name`,`brand`,`ram_type`,`capacity_gb`,`bus_speed`,`sticks_count`,`price_vnd`) VALUES
('Corsair Vengeance LPX 8GB DDR4 3200','Corsair','DDR4',8,3200,1,450000),
('Kingston Fury Beast 8GB DDR4 3200','Kingston','DDR4',8,3200,1,430000),
('Adata XPG Gammix D30 16GB (2x8) DDR4 3200','Adata','DDR4',16,3200,2,890000),
('Corsair Vengeance LPX 16GB (2x8) DDR4 3200','Corsair','DDR4',16,3200,2,980000),
('G.Skill Ripjaws V 16GB (2x8) DDR4 3600','G.Skill','DDR4',16,3600,2,1090000),
('Kingston Fury Beast 16GB (2x8) DDR4 3600','Kingston','DDR4',16,3600,2,1130000),
('Corsair Vengeance RGB Pro 16GB (2x8) DDR4 3600','Corsair','DDR4',16,3600,2,1290000),
('TeamGroup T-Force Delta RGB 32GB (2x16) DDR4 3200','TeamGroup','DDR4',32,3200,2,1790000),
('Corsair Vengeance LPX 32GB (2x16) DDR4 3200','Corsair','DDR4',32,3200,2,1850000),
('Kingston Fury Renegade 32GB (2x16) DDR4 3600','Kingston','DDR4',32,3600,2,2190000),
('Corsair Vengeance 8GB DDR5 5600','Corsair','DDR5',8,5600,1,590000),
('Kingston Fury Beast 16GB (2x8) DDR5 5200','Kingston','DDR5',16,5200,2,1450000),
('Corsair Vengeance 16GB (2x8) DDR5 5600','Corsair','DDR5',16,5600,2,1590000),
('G.Skill Ripjaws S5 16GB (2x8) DDR5 6000','G.Skill','DDR5',16,6000,2,1690000),
('TeamGroup Delta RGB 32GB (2x16) DDR5 5600','TeamGroup','DDR5',32,5600,2,2650000),
('Corsair Vengeance 32GB (2x16) DDR5 6000','Corsair','DDR5',32,6000,2,2890000),
('Kingston Fury Beast 32GB (2x16) DDR5 6000','Kingston','DDR5',32,6000,2,2790000),
('G.Skill Trident Z5 RGB 32GB (2x16) DDR5 6400','G.Skill','DDR5',32,6400,2,3450000),
('Corsair Dominator Platinum RGB 32GB (2x16) DDR5 6600','Corsair','DDR5',32,6600,2,4190000),
('Adata XPG Lancer Blade 64GB (2x32) DDR5 6000','Adata','DDR5',64,6000,2,5290000),
('Corsair Vengeance 64GB (2x32) DDR5 6400','Corsair','DDR5',64,6400,2,5750000),
('Kingston Fury Renegade 64GB (2x32) DDR5 6400','Kingston','DDR5',64,6400,2,5990000);

INSERT INTO `mainboards` (`name`,`brand`,`chipset`,`socket`,`form_factor`,`ram_type`,`ram_slots`,`price_vnd`) VALUES
('MSI PRO H610M-G DDR4','MSI','H610','LGA1700','Micro-ATX','DDR4',2,1800000),
('Gigabyte H610M H DDR4','Gigabyte','H610','LGA1700','Micro-ATX','DDR4',2,1700000),
('ASUS PRIME B660M-A D4','ASUS','B660','LGA1700','Micro-ATX','DDR4',4,2950000),
('MSI MAG B660M Mortar DDR4','MSI','B660','LGA1700','Micro-ATX','DDR4',4,3650000),
('ASRock B760M Pro RS/D4','ASRock','B760','LGA1700','Micro-ATX','DDR4',4,3250000),
('Gigabyte B760M DS3H DDR4','Gigabyte','B760','LGA1700','Micro-ATX','DDR4',4,2950000),
('ASUS TUF Gaming B760-PLUS WIFI D4','ASUS','B760','LGA1700','ATX','DDR4',4,4550000),
('MSI PRO Z790-P WIFI','MSI','Z790','LGA1700','ATX','DDR5',4,6850000),
('ASUS ROG STRIX Z790-A GAMING WIFI','ASUS','Z790','LGA1700','ATX','DDR5',4,9250000),
('Gigabyte Z790 AORUS ELITE AX','Gigabyte','Z790','LGA1700','ATX','DDR5',4,7990000),
('MSI B450M-A PRO MAX','MSI','B450','AM4','Micro-ATX','DDR4',2,1450000),
('Gigabyte B550M DS3H','Gigabyte','B550','AM4','Micro-ATX','DDR4',4,2200000),
('ASUS TUF GAMING B550-PLUS','ASUS','B550','AM4','ATX','DDR4',4,3400000),
('MSI MAG B550 Tomahawk','MSI','B550','AM4','ATX','DDR4',4,3850000),
('ASRock B550 Steel Legend','ASRock','B550','AM4','ATX','DDR4',4,4150000),
('Gigabyte A520M S2H','Gigabyte','A520','AM4','Micro-ATX','DDR4',2,1350000),
('ASUS PRIME A620M-K','ASUS','A620','AM5','Micro-ATX','DDR5',2,2350000),
('Gigabyte B650M DS3H','Gigabyte','B650','AM5','Micro-ATX','DDR5',4,3950000),
('MSI PRO B650M-A WIFI','MSI','B650','AM5','Micro-ATX','DDR5',4,4750000),
('ASUS TUF GAMING B650-PLUS WIFI','ASUS','B650','AM5','ATX','DDR5',4,6550000),
('Gigabyte X670 AORUS ELITE AX','Gigabyte','X670','AM5','ATX','DDR5',4,7990000),
('ASUS ROG STRIX X670E-E GAMING WIFI','ASUS','X670E','AM5','ATX','DDR5',4,12990000),
('MSI MEG X670E GODLIKE','MSI','X670E','AM5','E-ATX','DDR5',4,29900000);

INSERT INTO `vgas` (`name`,`brand`,`chipset_manufacturer`,`vram_gb`,`vram_type`,`recommended_psu_watts`,`length_mm`,`price_vnd`) VALUES
('Gigabyte GeForce GTX 1650 D6 OC 4G','Gigabyte','NVIDIA',4,'GDDR6',300,191,3200000),
('ASUS Dual GeForce RTX 3050 8GB','ASUS','NVIDIA',8,'GDDR6',450,200,5500000),
('MSI GeForce RTX 3060 Ventus 2X 12G','MSI','NVIDIA',12,'GDDR6',550,235,7800000),
('Gigabyte GeForce RTX 4060 EAGLE OC 8G','Gigabyte','NVIDIA',8,'GDDR6',450,272,9200000),
('ASUS Dual GeForce RTX 4060 8GB','ASUS','NVIDIA',8,'GDDR6',450,227,9800000),
('MSI GeForce RTX 4060 Ti Ventus 2X 8G OC','MSI','NVIDIA',8,'GDDR6',550,199,11900000),
('Gigabyte GeForce RTX 4070 WINDFORCE OC 12G','Gigabyte','NVIDIA',12,'GDDR6X',650,261,16500000),
('ASUS TUF Gaming GeForce RTX 4070 SUPER OC 12GB','ASUS','NVIDIA',12,'GDDR6X',650,305,18900000),
('MSI GeForce RTX 4070 Ti SUPER VENTUS 3X 16G OC','MSI','NVIDIA',16,'GDDR6X',700,308,23900000),
('Gigabyte GeForce RTX 4080 SUPER WINDFORCE V2 16G','Gigabyte','NVIDIA',16,'GDDR6X',750,342,31900000),
('ASUS ROG Strix GeForce RTX 4080 SUPER OC 16GB','ASUS','NVIDIA',16,'GDDR6X',850,357,38900000),
('MSI GeForce RTX 4090 GAMING X TRIO 24G','MSI','NVIDIA',24,'GDDR6X',850,337,55900000),
('Intel Arc A380 Challenger ITX 6GB','ASRock','Intel',6,'GDDR6',450,190,3900000),
('Intel Arc A580 Challenger 8GB','ASRock','Intel',8,'GDDR6',500,269,5600000),
('AMD Radeon RX 6600 Fighter 8GB','Sapphire','AMD',8,'GDDR6',450,193,5200000),
('Sapphire Pulse Radeon RX 7600 8GB','Sapphire','AMD',8,'GDDR6',550,240,7900000),
('Gigabyte Radeon RX 7700 XT GAMING OC 12G','Gigabyte','AMD',12,'GDDR6',650,302,12900000),
('ASUS TUF Gaming Radeon RX 7800 XT OC 16GB','ASUS','AMD',16,'GDDR6',700,319,15900000),
('Sapphire Nitro+ Radeon RX 7900 GRE 16GB','Sapphire','AMD',16,'GDDR6',700,320,18900000),
('ASUS TUF Gaming Radeon RX 7900 XT OC 20GB','ASUS','AMD',20,'GDDR6',750,352,23900000),
('Gigabyte Radeon RX 7900 XTX GAMING OC 24G','Gigabyte','AMD',24,'GDDR6',850,331,29900000),
('ASUS ProArt GeForce RTX 4070 Ti SUPER 16GB','ASUS','NVIDIA',16,'GDDR6X',700,300,24900000);

INSERT INTO `psus` (`name`,`brand`,`wattage`,`efficiency_rating`,`modular_type`,`form_factor`,`price_vnd`) VALUES
('Corsair CV450','Corsair',450,'80 Plus Bronze','Non-Modular','ATX',850000),
('Antec NeoECO 550W','Antec',550,'80 Plus Bronze','Semi-Modular','ATX',1190000),
('Cooler Master MWE 550 V2','Cooler Master',550,'80 Plus Bronze','Non-Modular','ATX',990000),
('Corsair CX650','Corsair',650,'80 Plus Bronze','Semi-Modular','ATX',1450000),
('Seasonic S12III 650','Seasonic',650,'80 Plus Bronze','Non-Modular','ATX',1590000),
('MSI MAG A650BN','MSI',650,'80 Plus Bronze','Non-Modular','ATX',1190000),
('Corsair RM650e','Corsair',650,'80 Plus Gold','Full-Modular','ATX',2490000),
('Seasonic Focus GX-650','Seasonic',650,'80 Plus Gold','Full-Modular','ATX',2990000),
('Cooler Master MWE Gold 750 V2','Cooler Master',750,'80 Plus Gold','Semi-Modular','ATX',2390000),
('MSI MPG A750GF','MSI',750,'80 Plus Gold','Full-Modular','ATX',2890000),
('Corsair RM750e','Corsair',750,'80 Plus Gold','Full-Modular','ATX',2990000),
('Seasonic Focus GX-750','Seasonic',750,'80 Plus Gold','Full-Modular','ATX',3490000),
('ASUS ROG Strix 850G','ASUS',850,'80 Plus Gold','Full-Modular','ATX',4190000),
('MSI MPG A850G PCIE5','MSI',850,'80 Plus Gold','Full-Modular','ATX',4590000),
('Corsair RM850e','Corsair',850,'80 Plus Gold','Full-Modular','ATX',4290000),
('Seasonic Focus GX-850','Seasonic',850,'80 Plus Gold','Full-Modular','ATX',4890000),
('Corsair RM1000x','Corsair',1000,'80 Plus Gold','Full-Modular','ATX',5790000),
('Seasonic Vertex GX-1000','Seasonic',1000,'80 Plus Gold','Full-Modular','ATX',6990000),
('MSI MPG A1000G PCIE5','MSI',1000,'80 Plus Gold','Full-Modular','ATX',6290000),
('ASUS ROG Thor 1200P2','ASUS',1200,'80 Plus Platinum','Full-Modular','ATX',9990000),
('Corsair HX1200i','Corsair',1200,'80 Plus Platinum','Full-Modular','ATX',10990000),
('Seasonic PRIME TX-1300','Seasonic',1300,'80 Plus Titanium','Full-Modular','ATX',13990000);

INSERT INTO `cases` (`name`,`brand`,`supported_mainboards`,`max_vga_length_mm`,`side_panel`,`price_vnd`) VALUES
('Xigmatek Gaming X 3F','Xigmatek','ATX, Micro-ATX, Mini-ITX',330,'Kính cường lực',890000),
('Cooler Master MasterBox Q300L','Cooler Master','Micro-ATX, Mini-ITX',360,'Mica/Kính',990000),
('DeepCool CC560','DeepCool','ATX, Micro-ATX, Mini-ITX',370,'Kính cường lực',1190000),
('Montech X3 Mesh','Montech','ATX, Micro-ATX, Mini-ITX',305,'Kính cường lực',1290000),
('MSI MAG Forge 100M','MSI','ATX, Micro-ATX, Mini-ITX',330,'Kính cường lực',1490000),
('Corsair 3000D Airflow','Corsair','ATX, Micro-ATX, Mini-ITX',360,'Kính cường lực',1790000),
('Lian Li LANCOOL 205 Mesh','Lian Li','ATX, Micro-ATX, Mini-ITX',350,'Kính cường lực',2190000),
('NZXT H5 Flow','NZXT','ATX, Micro-ATX, Mini-ITX',365,'Kính cường lực',2590000),
('Phanteks Eclipse G360A','Phanteks','ATX, Micro-ATX, Mini-ITX',400,'Kính cường lực',2690000),
('ASUS TUF Gaming GT301','ASUS','ATX, Micro-ATX, Mini-ITX',320,'Kính cường lực',2390000),
('Cooler Master TD500 Mesh V2','Cooler Master','ATX, Micro-ATX, Mini-ITX',410,'Kính cường lực',2890000),
('Lian Li O11 Dynamic EVO','Lian Li','ATX, Micro-ATX, Mini-ITX',425,'Kính cường lực',4490000),
('NZXT H7 Flow','NZXT','ATX, Micro-ATX, Mini-ITX',400,'Kính cường lực',3990000),
('Corsair 4000D Airflow','Corsair','ATX, Micro-ATX, Mini-ITX',360,'Kính cường lực',2590000),
('Thermaltake View 200 TG ARGB','Thermaltake','ATX, Micro-ATX, Mini-ITX',330,'Kính cường lực',1590000),
('MSI MPG GUNGNIR 300R AIRFLOW','MSI','ATX, Micro-ATX, Mini-ITX',360,'Kính cường lực',2890000),
('Fractal Design Pop Air','Fractal Design','ATX, Micro-ATX, Mini-ITX',405,'Kính cường lực',2990000),
('DeepCool CH560','DeepCool','ATX, Micro-ATX, Mini-ITX',380,'Kính cường lực',2290000),
('Montech AIR 903 MAX','Montech','ATX, Micro-ATX, Mini-ITX',400,'Kính cường lực',1990000),
('Lian Li LANCOOL 216','Lian Li','ATX, Micro-ATX, Mini-ITX',392,'Kính cường lực',2690000),
('Corsair 5000D Airflow','Corsair','ATX, Micro-ATX, Mini-ITX',420,'Kính cường lực',3390000),
('HYTE Y60','HYTE','ATX, Micro-ATX, Mini-ITX',375,'Kính cường lực',6590000);
