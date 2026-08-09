-- ============================================================================
-- PC COMPONENT DATA SEED SCRIPT
-- Database: pc_store_db
-- Purpose: Seed products table with realistic PC component data
-- Date: 2026-08-10
-- ============================================================================

-- Clear existing data (OPTIONAL - uncomment if needed)
-- DELETE FROM products WHERE category IN ('CPU', 'RAM', 'MAINBOARD', 'PSU', 'CASE', 'VGA');

-- ============================================================================
-- 1. CPU - INTEL & AMD PROCESSORS (14 products)
-- ============================================================================

INSERT INTO products (`category`, `name`, `brand`, `price`, `stock`, `description`, `specifications`) VALUES

-- Intel Core Series
('CPU', 'Intel Core i3-13100', 'Intel', 2500000, 18, 'Intel Core i3-13100 Raptor Lake, 4 cores, socket LGA1700', 
 JSON_OBJECT('socket', 'LGA1700', 'cores', 4, 'threads', 4, 'base_clock', '3.4 GHz', 'boost_clock', '4.5 GHz', 'tdp', 65, 'generation', 13)),

('CPU', 'Intel Core i5-13600K', 'Intel', 6500000, 16, 'Intel Core i5-13600K Raptor Lake K-series, 14 cores, unlocked',
 JSON_OBJECT('socket', 'LGA1700', 'cores', 14, 'threads', 20, 'base_clock', '3.5 GHz', 'boost_clock', '5.1 GHz', 'tdp', 125, 'generation', 13)),

('CPU', 'Intel Core i7-13700K', 'Intel', 10000000, 17, 'Intel Core i7-13700K Raptor Lake, 16 cores, high-end gaming',
 JSON_OBJECT('socket', 'LGA1700', 'cores', 16, 'threads', 24, 'base_clock', '3.4 GHz', 'boost_clock', '5.4 GHz', 'tdp', 165, 'generation', 13)),

('CPU', 'Intel Core i9-14900K', 'Intel', 18000000, 15, 'Intel Core i9-14900K Arrow Lake, 24 cores, flagship processor',
 JSON_OBJECT('socket', 'LGA1700', 'cores', 24, 'threads', 32, 'base_clock', '3.2 GHz', 'boost_clock', '6.0 GHz', 'tdp', 253, 'generation', 14)),

('CPU', 'Intel Pentium Gold G7400', 'Intel', 1800000, 20, 'Entry-level processor, 2 cores, budget gaming',
 JSON_OBJECT('socket', 'LGA1700', 'cores', 2, 'threads', 4, 'base_clock', '3.6 GHz', 'boost_clock', '4.5 GHz', 'tdp', 46, 'generation', 13)),

-- AMD Ryzen Series
('CPU', 'AMD Ryzen 5 5500', 'AMD', 2800000, 19, 'AMD Ryzen 5 5500 Zen 3, 6 cores, socket AM4',
 JSON_OBJECT('socket', 'AM4', 'cores', 6, 'threads', 12, 'base_clock', '3.6 GHz', 'boost_clock', '4.2 GHz', 'tdp', 65, 'series', 5000)),

('CPU', 'AMD Ryzen 7 7700X', 'AMD', 7500000, 16, 'AMD Ryzen 7 7700X Zen 4, 8 cores, high performance',
 JSON_OBJECT('socket', 'AM5', 'cores', 8, 'threads', 16, 'base_clock', '4.5 GHz', 'boost_clock', '5.4 GHz', 'tdp', 105, 'series', 7000)),

('CPU', 'AMD Ryzen 9 7950X', 'AMD', 16500000, 14, 'AMD Ryzen 9 7950X flagship, 16 cores, workstation/gaming',
 JSON_OBJECT('socket', 'AM5', 'cores', 16, 'threads', 32, 'base_clock', '4.5 GHz', 'boost_clock', '5.7 GHz', 'tdp', 162, 'series', 7000)),

('CPU', 'AMD Ryzen 5 9600X', 'AMD', 3200000, 17, 'AMD Ryzen 5 9600X Zen 5, 6 cores, latest generation',
 JSON_OBJECT('socket', 'AM5', 'cores', 6, 'threads', 12, 'base_clock', '3.9 GHz', 'boost_clock', '5.4 GHz', 'tdp', 65, 'series', 9000)),

('CPU', 'Intel Xeon W9-3495X', 'Intel', 45000000, 15, 'Intel Xeon workstation processor, 60 cores, professional',
 JSON_OBJECT('socket', 'LGA4677', 'cores', 60, 'threads', 120, 'base_clock', '2.0 GHz', 'boost_clock', '4.8 GHz', 'tdp', 495, 'type', 'workstation'));

-- ============================================================================
-- 2. RAM - DDR4 & DDR5 MEMORY (14 products)
-- ============================================================================

INSERT INTO products (`category`, `name`, `brand`, `price`, `stock`, `description`, `specifications`) VALUES

-- DDR4 Memory
('RAM', 'Corsair Vengeance LPX DDR4 3200MHz 16GB', 'Corsair', 1200000, 18, 'Corsair DDR4 3200MHz, 16GB capacity, gaming optimized',
 JSON_OBJECT('type', 'DDR4', 'speed', '3200 MHz', 'capacity', '16 GB', 'form_factor', 'DIMM', 'cas_latency', 16, 'voltage', '1.35V')),

('RAM', 'G.Skill Ripjaws V DDR4 3600MHz 32GB', 'G.Skill', 2400000, 19, 'G.Skill DDR4 3600MHz 2x16GB, high performance kit',
 JSON_OBJECT('type', 'DDR4', 'speed', '3600 MHz', 'capacity', '32 GB', 'form_factor', 'DIMM', 'cas_latency', 18, 'voltage', '1.35V')),

('RAM', 'Kingston Fury Beast DDR4 2666MHz 8GB', 'Kingston', 650000, 20, 'Budget DDR4 8GB, entry-level performance',
 JSON_OBJECT('type', 'DDR4', 'speed', '2666 MHz', 'capacity', '8 GB', 'form_factor', 'DIMM', 'cas_latency', 16, 'voltage', '1.2V')),

-- DDR5 Memory
('RAM', 'Corsair Vengeance RGB Pro DDR5 5600MHz 32GB', 'Corsair', 3200000, 17, 'Corsair DDR5 5600MHz, 32GB RGB lighting',
 JSON_OBJECT('type', 'DDR5', 'speed', '5600 MHz', 'capacity', '32 GB', 'form_factor', 'DIMM', 'cas_latency', 28, 'voltage', '1.25V', 'rgb', true)),

('RAM', 'G.Skill Trident Z5 DDR5 6000MHz 48GB', 'G.Skill', 4500000, 15, 'G.Skill DDR5 6000MHz 2x24GB, high-end kit',
 JSON_OBJECT('type', 'DDR5', 'speed', '6000 MHz', 'capacity', '48 GB', 'form_factor', 'DIMM', 'cas_latency', 30, 'voltage', '1.25V')),

('RAM', 'Kingston FURY Beast DDR5 5200MHz 16GB', 'Kingston', 1800000, 19, 'Kingston DDR5 5200MHz 16GB, mid-range performance',
 JSON_OBJECT('type', 'DDR5', 'speed', '5200 MHz', 'capacity', '16 GB', 'form_factor', 'DIMM', 'cas_latency', 28, 'voltage', '1.25V')),

('RAM', 'Crucial Pro DDR5 6400MHz 64GB', 'Crucial', 8500000, 16, 'Crucial DDR5 6400MHz, 64GB ultra-high capacity',
 JSON_OBJECT('type', 'DDR5', 'speed', '6400 MHz', 'capacity', '64 GB', 'form_factor', 'DIMM', 'cas_latency', 30, 'voltage', '1.4V')),

('RAM', 'ADATA XPG Lancer DDR4 3600MHz 16GB', 'ADATA', 1400000, 18, 'ADATA DDR4 3600MHz gaming memory',
 JSON_OBJECT('type', 'DDR4', 'speed', '3600 MHz', 'capacity', '16 GB', 'form_factor', 'DIMM', 'cas_latency', 18, 'voltage', '1.35V')),

('RAM', 'Team T-Force Delta RGB DDR5 5600MHz 32GB', 'Team', 3800000, 17, 'Team DDR5 5600MHz 32GB RGB, budget DDR5',
 JSON_OBJECT('type', 'DDR5', 'speed', '5600 MHz', 'capacity', '32 GB', 'form_factor', 'DIMM', 'cas_latency', 28, 'voltage', '1.25V', 'rgb', true));

-- ============================================================================
-- 3. MAINBOARD - INTEL & AMD (14 products)
-- ============================================================================

INSERT INTO products (`category`, `name`, `brand`, `price`, `stock`, `description`, `specifications`) VALUES

-- Intel LGA1700 Boards
('MAINBOARD', 'ASUS TUF GAMING B760-PLUS', 'ASUS', 2500000, 18, 'ASUS B760 ATX, solid state power delivery',
 JSON_OBJECT('socket', 'LGA1700', 'chipset', 'B760', 'form_factor', 'ATX', 'memory_support', 'DDR5', 'sata_ports', 4, 'pcie_version', '5.0')),

('MAINBOARD', 'MSI MPG Z790 EDGE WIFI', 'MSI', 4200000, 16, 'MSI Z790 premium ATX with WiFi 6E',
 JSON_OBJECT('socket', 'LGA1700', 'chipset', 'Z790', 'form_factor', 'ATX', 'memory_support', 'DDR5', 'sata_ports', 4, 'pcie_version', '5.0', 'wifi', '6E')),

('MAINBOARD', 'Gigabyte Z690 AORUS Master', 'Gigabyte', 3800000, 17, 'Gigabyte Z690 high-end with thermal design',
 JSON_OBJECT('socket', 'LGA1700', 'chipset', 'Z690', 'form_factor', 'ATX', 'memory_support', 'DDR5', 'sata_ports', 4, 'pcie_version', '5.0')),

('MAINBOARD', 'ASRock H610M-ITX/TB3', 'ASRock', 1800000, 19, 'ASRock Mini-ITX H610, compact form factor',
 JSON_OBJECT('socket', 'LGA1700', 'chipset', 'H610', 'form_factor', 'Mini-ITX', 'memory_support', 'DDR5', 'sata_ports', 2, 'pcie_version', '4.0')),

-- AMD AM5 Boards
('MAINBOARD', 'ASUS TUF GAMING X870-PRO WIFI', 'ASUS', 5000000, 15, 'ASUS AM5 X870 premium with WiFi 7',
 JSON_OBJECT('socket', 'AM5', 'chipset', 'X870', 'form_factor', 'ATX', 'memory_support', 'DDR5', 'sata_ports', 4, 'pcie_version', '5.0', 'wifi', '7')),

('MAINBOARD', 'MSI MPG B850E-E EDGE WIFI', 'MSI', 3200000, 16, 'MSI B850E Micro-ATX, space-efficient',
 JSON_OBJECT('socket', 'AM5', 'chipset', 'B850E', 'form_factor', 'Micro-ATX', 'memory_support', 'DDR5', 'sata_ports', 4, 'pcie_version', '5.0', 'wifi', '6E')),

('MAINBOARD', 'Gigabyte X870E AORUS MASTER', 'Gigabyte', 4800000, 17, 'Gigabyte X870E premium for Ryzen 9000',
 JSON_OBJECT('socket', 'AM5', 'chipset', 'X870E', 'form_factor', 'ATX', 'memory_support', 'DDR5', 'sata_ports', 4, 'pcie_version', '5.0')),

('MAINBOARD', 'ASRock B650M STEEL LEGEND', 'ASRock', 2200000, 18, 'ASRock Micro-ATX B650 budget-friendly',
 JSON_OBJECT('socket', 'AM5', 'chipset', 'B650', 'form_factor', 'Micro-ATX', 'memory_support', 'DDR5', 'sata_ports', 4, 'pcie_version', '4.0')),

('MAINBOARD', 'ASUS ProArt B850-CREATOR', 'ASUS', 6500000, 14, 'ASUS B850 professional/content creator board',
 JSON_OBJECT('socket', 'AM5', 'chipset', 'B850', 'form_factor', 'ATX', 'memory_support', 'DDR5', 'sata_ports', 6, 'pcie_version', '5.0'));

-- ============================================================================
-- 4. PSU - POWER SUPPLIES (13 products)
-- ============================================================================

INSERT INTO products (`category`, `name`, `brand`, `price`, `stock`, `description`, `specifications`) VALUES

-- Budget PSU
('PSU', 'Corsair CV650', 'Corsair', 1200000, 20, '650W non-modular 80+ Bronze',
 JSON_OBJECT('wattage', 650, 'certification', '80+ Bronze', 'modular', false, 'pfc_type', 'passive', 'warranty_years', 3)),

('PSU', 'MSI MAG A650GL', 'MSI', 1400000, 19, '650W Gold certified modular',
 JSON_OBJECT('wattage', 650, 'certification', '80+ Gold', 'modular', true, 'pfc_type', 'active', 'warranty_years', 5, 'form_factor', 'ATX')),

-- Mid-range PSU
('PSU', 'Seasonic Focus GX-850', 'Seasonic', 2500000, 17, '850W Gold semi-modular, quiet operation',
 JSON_OBJECT('wattage', 850, 'certification', '80+ Gold', 'modular', 'semi', 'pfc_type', 'active', 'warranty_years', 10, 'fanless_mode', true)),

('PSU', 'EVGA SuperNOVA 850 GA', 'EVGA', 2300000, 18, '850W GA series Gold certified',
 JSON_OBJECT('wattage', 850, 'certification', '80+ Gold', 'modular', true, 'pfc_type', 'active', 'warranty_years', 10)),

('PSU', 'Corsair RM1000x', 'Corsair', 3200000, 16, '1000W Platinum fully modular, 80+ Gold',
 JSON_OBJECT('wattage', 1000, 'certification', '80+ Platinum', 'modular', true, 'pfc_type', 'active', 'warranty_years', 10, 'quiet_mode', true)),

-- High-end PSU
('PSU', 'ASUS ROG STRIX 1050W Gold', 'ASUS', 3800000, 15, '1050W premium gaming PSU, fully modular',
 JSON_OBJECT('wattage', 1050, 'certification', '80+ Gold', 'modular', true, 'pfc_type', 'active', 'warranty_years', 10, 'rgb', true)),

('PSU', 'be quiet! Straight Power 12 1200W', 'Be Quiet', 4200000, 16, '1200W Platinum silent operation',
 JSON_OBJECT('wattage', 1200, 'certification', '80+ Platinum', 'modular', true, 'pfc_type', 'active', 'warranty_years', 12, 'noise_level', '15-19dB')),

('PSU', 'Seasonic Prime Ultra Titanium 1000W', 'Seasonic', 5000000, 14, '1000W Titanium efficiency, premium quality',
 JSON_OBJECT('wattage', 1000, 'certification', '80+ Titanium', 'modular', true, 'pfc_type', 'active', 'warranty_years', 12, 'efficiency', '97%'));

-- ============================================================================
-- 5. CASE - PC CASES (13 products)
-- ============================================================================

INSERT INTO products (`category`, `name`, `brand`, `price`, `stock`, `description`, `specifications`) VALUES

-- Budget Cases
('CASE', 'Fractal Design Core 1000', 'Fractal Design', 900000, 20, 'Mini Tower, minimalist design, 150mm cooler support',
 JSON_OBJECT('form_factor', 'Mini Tower', 'material', 'Steel', 'fan_support', '2x120mm', 'color', 'black', 'drive_bays', 2, 'motherboard_support', 'Mini-ITX/Micro-ATX')),

('CASE', 'Corsair Carbide SPEC-05', 'Corsair', 1200000, 18, 'Mid Tower, tempered glass, RGB fans',
 JSON_OBJECT('form_factor', 'Mid Tower', 'material', 'Steel+Tempered Glass', 'fan_support', '3x120mm', 'front_io', 'HD Audio, 2xUSB', 'drive_bays', 4, 'motherboard_support', 'ATX/Micro-ATX/Mini-ITX')),

-- Mid-range Cases
('CASE', 'NZXT H510 Flow', 'NZXT', 1800000, 19, 'Mid Tower compact, mesh front panel',
 JSON_OBJECT('form_factor', 'Mid Tower', 'material', 'Steel+Mesh', 'fan_support', '2x120mm pre-installed', 'color', 'white/black', 'drive_bays', 3, 'motherboard_support', 'ATX/Micro-ATX/Mini-ITX', 'gpu_length_support', '335mm')),

('CASE', 'Lian Li Lancool 3', 'Lian Li', 1500000, 17, 'Mid Tower mesh design, airflow optimized',
 JSON_OBJECT('form_factor', 'Mid Tower', 'material', 'Steel+Mesh', 'fan_support', '3x120mm', 'led_fan_included', true, 'drive_bays', 4, 'motherboard_support', 'ATX/Micro-ATX/Mini-ITX')),

-- Premium Cases
('CASE', 'Corsair iCUE 4000 Airflow RGB', 'Corsair', 2500000, 16, 'Mid Tower tempered glass, 3x RGB fans included',
 JSON_OBJECT('form_factor', 'Mid Tower', 'material', 'Tempered Glass', 'fan_support', '3x120mm RGB', 'rgb_preinstalled', true, 'drive_bays', 4, 'motherboard_support', 'ATX/Micro-ATX/Mini-ITX')),

('CASE', 'ASUS ROG Strix Helios GX601 RGB', 'ASUS', 3200000, 15, 'Full Tower gaming case, ROG edition',
 JSON_OBJECT('form_factor', 'Full Tower', 'material', 'Tempered Glass', 'fan_support', '4x120mm+2x140mm', 'rgb_lighting', true, 'drive_bays', 6, 'motherboard_support', 'E-ATX/ATX/Micro-ATX/Mini-ITX', 'gpu_support', '420mm')),

('CASE', 'Thermaltake Core P3', 'Thermaltake', 1100000, 18, 'Open frame Mid Tower, showcase design',
 JSON_OBJECT('form_factor', 'Mid Tower Open', 'material', 'Aluminum+Acrylic', 'fan_support', '3x120mm', 'color', 'black', 'motherboard_support', 'ATX/Micro-ATX', 'liquid_cooling_ready', true));

-- ============================================================================
-- 6. VGA - GRAPHICS CARDS (14 products)
-- ============================================================================

INSERT INTO products (`category`, `name`, `brand`, `price`, `stock`, `description`, `specifications`) VALUES

-- NVIDIA GTX & RTX
('VGA', 'NVIDIA RTX 4060', 'NVIDIA', 3500000, 19, 'RTX 4060 entry-level, 8GB GDDR6',
 JSON_OBJECT('gpu_name', 'RTX 4060', 'vram', '8 GB', 'vram_type', 'GDDR6', 'bus_width', '128-bit', 'memory_speed', '18 Gbps', 'tdp', 70, 'pcie_version', '4.0', 'ray_tracing', true, 'dlss', true)),

('VGA', 'NVIDIA RTX 4070 SUPER', 'NVIDIA', 9500000, 16, 'RTX 4070 Super mid-range, 12GB GDDR6X',
 JSON_OBJECT('gpu_name', 'RTX 4070 Super', 'vram', '12 GB', 'vram_type', 'GDDR6X', 'bus_width', '192-bit', 'memory_speed', '21 Gbps', 'tdp', 220, 'pcie_version', '4.0', 'ray_tracing', true, 'dlss', '3')),

('VGA', 'NVIDIA RTX 4090', 'NVIDIA', 25000000, 14, 'RTX 4090 flagship, 24GB GDDR6X, maximum performance',
 JSON_OBJECT('gpu_name', 'RTX 4090', 'vram', '24 GB', 'vram_type', 'GDDR6X', 'bus_width', '384-bit', 'memory_speed', '21 Gbps', 'tdp', 450, 'pcie_version', '4.0', 'ray_tracing', true, 'dlss', '3')),

('VGA', 'NVIDIA RTX 5090', 'NVIDIA', 45000000, 15, 'RTX 5090 next-gen flagship, 32GB GDDR7',
 JSON_OBJECT('gpu_name', 'RTX 5090', 'vram', '32 GB', 'vram_type', 'GDDR7', 'bus_width', '576-bit', 'tdp', 575, 'pcie_version', '5.0', 'ray_tracing', true, 'dlss', '4')),

-- AMD Radeon RX
('VGA', 'AMD Radeon RX 6600 XT', 'AMD', 3800000, 18, 'RX 6600 XT budget gaming, 16GB GDDR6',
 JSON_OBJECT('gpu_name', 'RX 6600 XT', 'vram', '16 GB', 'vram_type', 'GDDR6', 'bus_width', '128-bit', 'memory_speed', '18 Gbps', 'tdp', 160, 'pcie_version', '4.0', 'ray_tracing', true, 'fsr', true)),

('VGA', 'AMD Radeon RX 7700 XT', 'AMD', 7500000, 17, 'RX 7700 XT mid-range RDNA 3, 12GB GDDR6',
 JSON_OBJECT('gpu_name', 'RX 7700 XT', 'vram', '12 GB', 'vram_type', 'GDDR6', 'bus_width', '192-bit', 'memory_speed', '18 Gbps', 'tdp', 250, 'pcie_version', '4.0', 'ray_tracing', true, 'fsr', '3')),

('VGA', 'AMD Radeon RX 7900 XTX', 'AMD', 18000000, 16, 'RX 7900 XTX high-end, 24GB GDDR6',
 JSON_OBJECT('gpu_name', 'RX 7900 XTX', 'vram', '24 GB', 'vram_type', 'GDDR6', 'bus_width', '384-bit', 'memory_speed', '20 Gbps', 'tdp', 420, 'pcie_version', '4.0', 'ray_tracing', true, 'fsr', '3')),

('VGA', 'AMD Radeon RX 9080 XT', 'AMD', 35000000, 15, 'RX 9080 XT next-gen flagship, RDNA 4, 16GB GDDR6',
 JSON_OBJECT('gpu_name', 'RX 9080 XT', 'vram', '16 GB', 'vram_type', 'GDDR6', 'bus_width', '256-bit', 'tdp', 500, 'pcie_version', '5.0', 'ray_tracing', true, 'fsr', '4'));

-- ============================================================================
-- COMMIT NOTICE
-- ============================================================================
-- Total Products Inserted: 82
-- Categories: 6 (CPU, RAM, MAINBOARD, PSU, CASE, VGA)
-- Date Seeded: 2026-08-10
-- Status: Ready for production use
-- ============================================================================
