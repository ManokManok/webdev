<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Stock;
use App\Entity\Supplier;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $categories = $this->createCategories($manager);
        $suppliers = $this->createSuppliers($manager);
        $this->createStockItems($manager, $suppliers);
        $this->createProducts($manager, $suppliers, $categories);
        $this->createAdminUser($manager);
        $this->createDemoCustomer($manager);
        $manager->flush();
    }

    private function createCategories(ObjectManager $manager): array
    {
        $categoryData = [
            ['name' => 'Electronics'],
            ['name' => 'Hardware'],
            ['name' => 'Peripherals'],
            ['name' => 'Networking'],
            ['name' => 'Storage'],
            ['name' => 'Components'],
            ['name' => 'Accessories'],
            ['name' => 'Office Supplies'],
            ['name' => 'Cables & Adapters'],
            ['name' => 'Power Supplies'],
        ];

        $categories = [];
        foreach ($categoryData as $data) {
            $category = new Category();
            $category->setName($data['name']);
            $categories[] = $category;
            $manager->persist($category);
        }
        return $categories;
    }

    private function createSuppliers(ObjectManager $manager): array
    {
        $supplierData = [
            ['name' => 'TechSource Wholesale', 'contact' => '+63 2 8123 4567'],
            ['name' => 'Global Electronics Supply', 'contact' => '+63 2 8987 6543'],
            ['name' => 'PC Components Pro', 'contact' => '+63 2 8543 2190'],
            ['name' => 'Network Solutions Inc.', 'contact' => '+63 2 8765 4321'],
            ['name' => 'Digital Storage Systems', 'contact' => '+63 2 8234 5678'],
            ['name' => 'OfficeMart Supplies', 'contact' => '+63 2 8456 7890'],
            ['name' => 'CableConnect Distributors', 'contact' => '+63 2 8678 9012'],
            ['name' => 'PowerTech Solutions', 'contact' => '+63 2 8890 1234'],
        ];

        $suppliers = [];
        foreach ($supplierData as $data) {
            $supplier = new Supplier();
            $supplier->setName($data['name']);
            $supplier->setContact($data['contact']);
            $suppliers[] = $supplier;
            $manager->persist($supplier);
        }
        return $suppliers;
    }

    private function createStockItems(ObjectManager $manager, array $suppliers): void
    {
        $stockItems = [
            [
                'supplier' => 0,
                'items' => [
                    ['name' => 'Dell Latitude 5520 Laptop', 'sku' => 'DELL-LAT-5520', 'quantity' => 25, 'minThreshold' => 5, 'unit' => 'units', 'unitCost' => '45000.00', 'location' => 'Warehouse A-1'],
                    ['name' => 'HP ProBook 450 G8', 'sku' => 'HP-PB450-G8', 'quantity' => 20, 'minThreshold' => 5, 'unit' => 'units', 'unitCost' => '42000.00', 'location' => 'Warehouse A-1'],
                    ['name' => 'Dell 24" Full HD Monitor', 'sku' => 'DELL-MON-24', 'quantity' => 40, 'minThreshold' => 10, 'unit' => 'units', 'unitCost' => '8500.00', 'location' => 'Warehouse A-2'],
                    ['name' => 'Samsung 27" Curved Monitor', 'sku' => 'SAM-MON-27C', 'quantity' => 15, 'minThreshold' => 5, 'unit' => 'units', 'unitCost' => '12000.00', 'location' => 'Warehouse A-2'],
                    ['name' => 'Logitech MX Master 3 Mouse', 'sku' => 'LOG-MX-M3', 'quantity' => 60, 'minThreshold' => 15, 'unit' => 'units', 'unitCost' => '4500.00', 'location' => 'Warehouse B-1'],
                    ['name' => 'Keychron K2 Mechanical Keyboard', 'sku' => 'KEY-K2-RGB', 'quantity' => 35, 'minThreshold' => 10, 'unit' => 'units', 'unitCost' => '5500.00', 'location' => 'Warehouse B-1'],
                    ['name' => 'Logitech C920 HD Webcam', 'sku' => 'LOG-C920-HD', 'quantity' => 45, 'minThreshold' => 12, 'unit' => 'units', 'unitCost' => '3500.00', 'location' => 'Warehouse B-2'],
                    ['name' => 'Anker USB-C Hub 7-in-1', 'sku' => 'ANK-USB-7IN1', 'quantity' => 80, 'minThreshold' => 20, 'unit' => 'units', 'unitCost' => '2200.00', 'location' => 'Warehouse B-2'],
                    ['name' => 'Samsung T7 1TB SSD', 'sku' => 'SAM-T7-1TB', 'quantity' => 30, 'minThreshold' => 8, 'unit' => 'units', 'unitCost' => '6500.00', 'location' => 'Warehouse C-1'],
                    ['name' => 'Seagate 2TB External HDD', 'sku' => 'SEA-EXT-2TB', 'quantity' => 50, 'minThreshold' => 15, 'unit' => 'units', 'unitCost' => '3500.00', 'location' => 'Warehouse C-1'],
                ]
            ],
            [
                'supplier' => 1,
                'items' => [
                    ['name' => 'Intel Core i7-12700', 'sku' => 'INT-I7-12700', 'quantity' => 18, 'minThreshold' => 5, 'unit' => 'units', 'unitCost' => '18500.00', 'location' => 'Warehouse A-3'],
                    ['name' => 'Intel Core i5-12400', 'sku' => 'INT-I5-12400', 'quantity' => 22, 'minThreshold' => 6, 'unit' => 'units', 'unitCost' => '12000.00', 'location' => 'Warehouse A-3'],
                    ['name' => 'AMD Ryzen 5 5600X', 'sku' => 'AMD-R5-5600X', 'quantity' => 16, 'minThreshold' => 4, 'unit' => 'units', 'unitCost' => '14000.00', 'location' => 'Warehouse A-3'],
                    ['name' => 'Kingston 16GB DDR4 RAM', 'sku' => 'KIN-RAM-16G', 'quantity' => 100, 'minThreshold' => 25, 'unit' => 'sticks', 'unitCost' => '3200.00', 'location' => 'Warehouse A-4'],
                    ['name' => 'Corsair 32GB DDR4 RAM', 'sku' => 'COR-RAM-32G', 'quantity' => 60, 'minThreshold' => 15, 'unit' => 'sticks', 'unitCost' => '5800.00', 'location' => 'Warehouse A-4'],
                    ['name' => 'ASUS PRIME B660M Motherboard', 'sku' => 'ASU-B660M', 'quantity' => 14, 'minThreshold' => 5, 'unit' => 'units', 'unitCost' => '8500.00', 'location' => 'Warehouse A-5'],
                    ['name' => 'RTX 3060 12GB GPU', 'sku' => 'NV-RTX3060', 'quantity' => 8, 'minThreshold' => 3, 'unit' => 'units', 'unitCost' => '22000.00', 'location' => 'Warehouse A-6'],
                    ['name' => 'GTX 1650 4GB GPU', 'sku' => 'NV-GTX1650', 'quantity' => 12, 'minThreshold' => 4, 'unit' => 'units', 'unitCost' => '9500.00', 'location' => 'Warehouse A-6'],
                    ['name' => 'Cooler Master Hyper 212', 'sku' => 'CM-HYP-212', 'quantity' => 25, 'minThreshold' => 8, 'unit' => 'units', 'unitCost' => '1800.00', 'location' => 'Warehouse A-7'],
                    ['name' => 'Arctic MX-4 Thermal Paste', 'sku' => 'ARC-MX4-T', 'quantity' => 150, 'minThreshold' => 40, 'unit' => 'tubes', 'unitCost' => '250.00', 'location' => 'Warehouse A-7'],
                ]
            ],
            [
                'supplier' => 2,
                'items' => [
                    ['name' => 'Secretlab Titan Gaming Chair', 'sku' => 'SEC-TITAN-XXL', 'quantity' => 10, 'minThreshold' => 3, 'unit' => 'units', 'unitCost' => '22000.00', 'location' => 'Warehouse D-1'],
                    ['name' => 'Razer DeathAdder V2 Mouse', 'sku' => 'RAZ-DA-V2', 'quantity' => 40, 'minThreshold' => 12, 'unit' => 'units', 'unitCost' => '3500.00', 'location' => 'Warehouse D-2'],
                    ['name' => 'Razer BlackWidow Keyboard', 'sku' => 'RAZ-BW-V3', 'quantity' => 28, 'minThreshold' => 8, 'unit' => 'units', 'unitCost' => '7500.00', 'location' => 'Warehouse D-2'],
                    ['name' => 'HyperX Cloud II Headset', 'sku' => 'HX-CLOUD-II', 'quantity' => 35, 'minThreshold' => 10, 'unit' => 'units', 'unitCost' => '4500.00', 'location' => 'Warehouse D-3'],
                    ['name' => 'SteelSeries QcK XXL Mousepad', 'sku' => 'SS-QCK-XXL', 'quantity' => 55, 'minThreshold' => 15, 'unit' => 'units', 'unitCost' => '1200.00', 'location' => 'Warehouse D-3'],
                    ['name' => 'Blue Yeti Microphone', 'sku' => 'BLU-YETI-B', 'quantity' => 18, 'minThreshold' => 5, 'unit' => 'units', 'unitCost' => '8500.00', 'location' => 'Warehouse D-4'],
                    ['name' => 'Elgato HD60 S+ Capture Card', 'sku' => 'ELG-HD60SP', 'quantity' => 12, 'minThreshold' => 4, 'unit' => 'units', 'unitCost' => '11000.00', 'location' => 'Warehouse D-4'],
                    ['name' => 'Ergotron LX Monitor Arm', 'sku' => 'ERG-LX-ARM', 'quantity' => 20, 'minThreshold' => 6, 'unit' => 'units', 'unitCost' => '6500.00', 'location' => 'Warehouse D-5'],
                    ['name' => 'BenQ ScreenBar Desk Lamp', 'sku' => 'BNQ-SBAR-PLUS', 'quantity' => 30, 'minThreshold' => 8, 'unit' => 'units', 'unitCost' => '5500.00', 'location' => 'Warehouse D-5'],
                    ['name' => 'Cable Management Kit', 'sku' => 'GEN-CABLE-KIT', 'quantity' => 100, 'minThreshold' => 30, 'unit' => 'sets', 'unitCost' => '450.00', 'location' => 'Warehouse D-6'],
                ]
            ],
            [
                'supplier' => 3,
                'items' => [
                    ['name' => 'TP-Link Archer AX73 Router', 'sku' => 'TPL-AX73-WIFI', 'quantity' => 20, 'minThreshold' => 6, 'unit' => 'units', 'unitCost' => '7500.00', 'location' => 'Warehouse E-1'],
                    ['name' => 'Cisco 24-Port Gigabit Switch', 'sku' => 'CIS-SW24-GIG', 'quantity' => 12, 'minThreshold' => 4, 'unit' => 'units', 'unitCost' => '18500.00', 'location' => 'Warehouse E-1'],
                    ['name' => 'Ubiquiti U6-Pro Access Point', 'sku' => 'UBQ-U6-PRO', 'quantity' => 25, 'minThreshold' => 8, 'unit' => 'units', 'unitCost' => '12500.00', 'location' => 'Warehouse E-2'],
                    ['name' => 'Cat6 Network Cable 305m', 'sku' => 'CAT6-305M-BX', 'quantity' => 15, 'minThreshold' => 5, 'unit' => 'boxes', 'unitCost' => '6500.00', 'location' => 'Warehouse E-3'],
                    ['name' => '24-Port Cat6 Patch Panel', 'sku' => 'PP24-CAT6', 'quantity' => 18, 'minThreshold' => 6, 'unit' => 'units', 'unitCost' => '2800.00', 'location' => 'Warehouse E-3'],
                    ['name' => 'RJ45 Cat6 Connectors (100pc)', 'sku' => 'RJ45-C6-100', 'quantity' => 80, 'minThreshold' => 25, 'unit' => 'packs', 'unitCost' => '450.00', 'location' => 'Warehouse E-4'],
                    ['name' => 'Network Cable Tester', 'sku' => 'FLU-NET-TONE', 'quantity' => 8, 'minThreshold' => 3, 'unit' => 'units', 'unitCost' => '8500.00', 'location' => 'Warehouse E-4'],
                    ['name' => 'Fiber Optic Cable OM3 10m', 'sku' => 'FIB-OM3-10M', 'quantity' => 30, 'minThreshold' => 10, 'unit' => 'cables', 'unitCost' => '1200.00', 'location' => 'Warehouse E-5'],
                    ['name' => 'APC 1500VA UPS', 'sku' => 'APC-1500VA', 'quantity' => 10, 'minThreshold' => 4, 'unit' => 'units', 'unitCost' => '18500.00', 'location' => 'Warehouse E-6'],
                    ['name' => '8-Outlet Surge Protector', 'sku' => 'APC-SURG-8OT', 'quantity' => 40, 'minThreshold' => 12, 'unit' => 'units', 'unitCost' => '2200.00', 'location' => 'Warehouse E-6'],
                ]
            ],
            [
                'supplier' => 4,
                'items' => [
                    ['name' => 'Synology DS920+ NAS', 'sku' => 'SYN-DS920P', 'quantity' => 8, 'minThreshold' => 3, 'unit' => 'units', 'unitCost' => '35000.00', 'location' => 'Warehouse F-1'],
                    ['name' => 'WD Red 4TB NAS Drive', 'sku' => 'WD-RED-4TB', 'quantity' => 40, 'minThreshold' => 12, 'unit' => 'drives', 'unitCost' => '6500.00', 'location' => 'Warehouse F-2'],
                    ['name' => 'WD Red Plus 8TB NAS Drive', 'sku' => 'WD-RED-8TB', 'quantity' => 24, 'minThreshold' => 8, 'unit' => 'drives', 'unitCost' => '12500.00', 'location' => 'Warehouse F-2'],
                    ['name' => 'Samsung 980 Pro 1TB NVMe', 'sku' => 'SAM-980P-1TB', 'quantity' => 50, 'minThreshold' => 15, 'unit' => 'drives', 'unitCost' => '8500.00', 'location' => 'Warehouse F-3'],
                    ['name' => 'Crucial MX500 1TB SSD', 'sku' => 'CRU-MX500-1T', 'quantity' => 60, 'minThreshold' => 20, 'unit' => 'drives', 'unitCost' => '5500.00', 'location' => 'Warehouse F-3'],
                    ['name' => 'SanDisk 128GB USB Drive', 'sku' => 'SD-USB128G', 'quantity' => 120, 'minThreshold' => 35, 'unit' => 'units', 'unitCost' => '650.00', 'location' => 'Warehouse F-4'],
                    ['name' => 'SanDisk Extreme 256GB SD Card', 'sku' => 'SD-EXT256G', 'quantity' => 80, 'minThreshold' => 25, 'unit' => 'units', 'unitCost' => '2200.00', 'location' => 'Warehouse F-4'],
                    ['name' => 'LTO-9 18TB Backup Tape', 'sku' => 'LTO9-18TB-T', 'quantity' => 20, 'minThreshold' => 6, 'unit' => 'tapes', 'unitCost' => '15000.00', 'location' => 'Warehouse F-5'],
                    ['name' => 'Cloud Backup 1TB License', 'sku' => 'CLD-BAK-1TB', 'quantity' => 200, 'minThreshold' => 60, 'unit' => 'licenses', 'unitCost' => '3500.00', 'location' => 'Warehouse F-6'],
                    ['name' => '3.5" USB 3.0 Enclosure', 'sku' => 'ENC-35USB3', 'quantity' => 35, 'minThreshold' => 12, 'unit' => 'units', 'unitCost' => '1200.00', 'location' => 'Warehouse F-7'],
                ]
            ],
            [
                'supplier' => 5,
                'items' => [
                    ['name' => 'A4 Paper 500 sheets', 'sku' => 'A4-PAP-500', 'quantity' => 500, 'minThreshold' => 150, 'unit' => 'reams', 'unitCost' => '180.00', 'location' => 'Warehouse G-1'],
                    ['name' => 'HP LaserJet Pro M404', 'sku' => 'HP-LJ-M404', 'quantity' => 12, 'minThreshold' => 4, 'unit' => 'units', 'unitCost' => '18500.00', 'location' => 'Warehouse G-2'],
                    ['name' => 'HP 26A Toner Cartridge', 'sku' => 'HP-26A-BLK', 'quantity' => 45, 'minThreshold' => 15, 'unit' => 'cartridges', 'unitCost' => '4500.00', 'location' => 'Warehouse G-2'],
                    ['name' => 'Canon 745 Color Ink', 'sku' => 'CN-745-CMY', 'quantity' => 60, 'minThreshold' => 20, 'unit' => 'cartridges', 'unitCost' => '850.00', 'location' => 'Warehouse G-3'],
                    ['name' => 'Heavy Duty Stapler', 'sku' => 'STP-HD-40SH', 'quantity' => 30, 'minThreshold' => 10, 'unit' => 'units', 'unitCost' => '350.00', 'location' => 'Warehouse G-4'],
                    ['name' => 'Staples 5000 pcs', 'sku' => 'STAP-5000', 'quantity' => 100, 'minThreshold' => 30, 'unit' => 'boxes', 'unitCost' => '85.00', 'location' => 'Warehouse G-4'],
                    ['name' => 'Binder Clips Assorted', 'sku' => 'BND-CLIP-AS', 'quantity' => 80, 'minThreshold' => 25, 'unit' => 'boxes', 'unitCost' => '120.00', 'location' => 'Warehouse G-4'],
                    ['name' => '48x36" Whiteboard', 'sku' => 'WB-48X36-IN', 'quantity' => 15, 'minThreshold' => 5, 'unit' => 'units', 'unitCost' => '2200.00', 'location' => 'Warehouse G-5'],
                    ['name' => 'Whiteboard Markers 8pc', 'sku' => 'WB-MARK-8PC', 'quantity' => 120, 'minThreshold' => 40, 'unit' => 'sets', 'unitCost' => '180.00', 'location' => 'Warehouse G-5'],
                    ['name' => '4-Drawer Filing Cabinet', 'sku' => 'FIL-4DR-STL', 'quantity' => 8, 'minThreshold' => 3, 'unit' => 'units', 'unitCost' => '5500.00', 'location' => 'Warehouse G-6'],
                ]
            ],
            [
                'supplier' => 6,
                'items' => [
                    ['name' => 'HDMI 2.1 Cable 2m', 'sku' => 'HDMI-2M-8K', 'quantity' => 150, 'minThreshold' => 50, 'unit' => 'cables', 'unitCost' => '450.00', 'location' => 'Warehouse H-1'],
                    ['name' => 'HDMI 2.0 Cable 5m', 'sku' => 'HDMI-5M-4K', 'quantity' => 100, 'minThreshold' => 35, 'unit' => 'cables', 'unitCost' => '650.00', 'location' => 'Warehouse H-1'],
                    ['name' => 'DisplayPort 1.4 Cable 2m', 'sku' => 'DP-14-2M', 'quantity' => 80, 'minThreshold' => 25, 'unit' => 'cables', 'unitCost' => '550.00', 'location' => 'Warehouse H-2'],
                    ['name' => 'USB-C 100W Cable 2m', 'sku' => 'USBC-2M-100', 'quantity' => 200, 'minThreshold' => 60, 'unit' => 'cables', 'unitCost' => '280.00', 'location' => 'Warehouse H-2'],
                    ['name' => 'USB-C to HDMI Adapter', 'sku' => 'USBC-HDMI-4K', 'quantity' => 60, 'minThreshold' => 20, 'unit' => 'units', 'unitCost' => '850.00', 'location' => 'Warehouse H-3'],
                    ['name' => 'Cat6 Ethernet 3m', 'sku' => 'ETH-C6-3M', 'quantity' => 300, 'minThreshold' => 100, 'unit' => 'cables', 'unitCost' => '120.00', 'location' => 'Warehouse H-3'],
                    ['name' => '5-Outlet Extension 3m', 'sku' => 'EXT-5OT-3M', 'quantity' => 80, 'minThreshold' => 25, 'unit' => 'units', 'unitCost' => '450.00', 'location' => 'Warehouse H-4'],
                    ['name' => 'VGA Cable 1.5m', 'sku' => 'VGA-15M-MM', 'quantity' => 40, 'minThreshold' => 15, 'unit' => 'cables', 'unitCost' => '280.00', 'location' => 'Warehouse H-4'],
                    ['name' => '3.5mm AUX Cable 2m', 'sku' => 'AUX-35-2M', 'quantity' => 200, 'minThreshold' => 60, 'unit' => 'cables', 'unitCost' => '85.00', 'location' => 'Warehouse H-5'],
                    ['name' => 'DVI to HDMI Adapter', 'sku' => 'DVI-HDMI-AD', 'quantity' => 35, 'minThreshold' => 12, 'unit' => 'units', 'unitCost' => '350.00', 'location' => 'Warehouse H-5'],
                ]
            ],
            [
                'supplier' => 7,
                'items' => [
                    ['name' => 'Corsair RM750 PSU', 'sku' => 'COR-RM750-G', 'quantity' => 20, 'minThreshold' => 6, 'unit' => 'units', 'unitCost' => '7500.00', 'location' => 'Warehouse I-1'],
                    ['name' => 'Seasonic 650W Bronze PSU', 'sku' => 'SEA-650-BRZ', 'quantity' => 18, 'minThreshold' => 6, 'unit' => 'units', 'unitCost' => '4500.00', 'location' => 'Warehouse I-1'],
                    ['name' => 'Universal 65W Laptop Charger', 'sku' => 'LAP-CHG-65W', 'quantity' => 50, 'minThreshold' => 15, 'unit' => 'units', 'unitCost' => '1200.00', 'location' => 'Warehouse I-2'],
                    ['name' => 'Dell 90W Original Charger', 'sku' => 'DELL-CHG90W', 'quantity' => 30, 'minThreshold' => 10, 'unit' => 'units', 'unitCost' => '2200.00', 'location' => 'Warehouse I-2'],
                    ['name' => 'Anker 20000mAh Power Bank', 'sku' => 'ANK-PB20K', 'quantity' => 60, 'minThreshold' => 20, 'unit' => 'units', 'unitCost' => '1850.00', 'location' => 'Warehouse I-3'],
                    ['name' => 'Mi 10000mAh Power Bank', 'sku' => 'MI-PB10K', 'quantity' => 100, 'minThreshold' => 30, 'unit' => 'units', 'unitCost' => '850.00', 'location' => 'Warehouse I-3'],
                    ['name' => '1000VA Voltage Regulator', 'sku' => 'AVR-1000VA', 'quantity' => 15, 'minThreshold' => 5, 'unit' => 'units', 'unitCost' => '2800.00', 'location' => 'Warehouse I-4'],
                    ['name' => '6-Outlet USB Surge Protector', 'sku' => 'SURG-6OT-USB', 'quantity' => 45, 'minThreshold' => 15, 'unit' => 'units', 'unitCost' => '850.00', 'location' => 'Warehouse I-4'],
                    ['name' => 'UPS 12V 7Ah Battery', 'sku' => 'UPS-BAT-12V7', 'quantity' => 25, 'minThreshold' => 8, 'unit' => 'units', 'unitCost' => '1200.00', 'location' => 'Warehouse I-5'],
                    ['name' => '15W Wireless Charger Pad', 'sku' => 'WCH-PAD-15W', 'quantity' => 80, 'minThreshold' => 25, 'unit' => 'units', 'unitCost' => '650.00', 'location' => 'Warehouse I-5'],
                ]
            ],
        ];

        foreach ($stockItems as $supplierGroup) {
            $supplier = $suppliers[$supplierGroup['supplier']];
            foreach ($supplierGroup['items'] as $itemData) {
                $stock = new Stock();
                $stock->setItemName($itemData['name']);
                $stock->setSku($itemData['sku']);
                $stock->setQuantity($itemData['quantity']);
                $stock->setMinThreshold($itemData['minThreshold']);
                $stock->setUnit($itemData['unit']);
                $stock->setUnitCost($itemData['unitCost']);
                $stock->setLocation($itemData['location']);
                $stock->setDescription('Stock item for ' . $itemData['name']);
                $stock->setSupplier($supplier);
                $manager->persist($stock);
            }
        }
    }

    private function createProducts(ObjectManager $manager, array $suppliers, array $categories): void
    {
        // Repair services shown on the public site and mobile customer app
        $productsData = [
            ['name' => 'Screen Repair & Replacement', 'issue' => 'Cracked, shattered, or unresponsive screens. OEM-grade panels, 90-day warranty.', 'price' => 1800.00, 'category' => 0, 'supplier' => 0],
            ['name' => 'Battery Replacement', 'issue' => 'Fast-draining or swollen batteries replaced with certified cells.', 'price' => 1200.00, 'category' => 0, 'supplier' => 0],
            ['name' => 'Water Damage Recovery', 'issue' => 'Ultrasonic cleaning and component-level recovery for liquid damage.', 'price' => 2500.00, 'category' => 0, 'supplier' => 0],
            ['name' => 'Charging Port Repair', 'issue' => 'Loose ports, slow charging, and USB-C/Lightning connector replacement.', 'price' => 900.00, 'category' => 0, 'supplier' => 0],
            ['name' => 'Software & OS Issues', 'issue' => 'Boot loops, update failures, virus removal, and data-safe restores.', 'price' => 800.00, 'category' => 0, 'supplier' => 0],
            ['name' => 'Camera Module Replacement', 'issue' => 'Blurry, cracked, or non-working front/rear camera modules.', 'price' => 1500.00, 'category' => 0, 'supplier' => 0],
            ['name' => 'Speaker & Microphone Repair', 'issue' => 'Distorted audio, no sound, or failed call microphones.', 'price' => 1100.00, 'category' => 0, 'supplier' => 0],
            ['name' => 'Back Glass & Housing Repair', 'issue' => 'Shattered back glass and bent frame straightening for major brands.', 'price' => 2200.00, 'category' => 0, 'supplier' => 0],
        ];

        foreach ($productsData as $productData) {
            $product = new Product();
            $product->setName($productData['name']);
            $product->setIssue($productData['issue']);
            $product->setPrice($productData['price']);
            $product->setCategory($categories[$productData['category']]);
            $product->setSupplier($suppliers[$productData['supplier']]);
            $manager->persist($product);
        }
    }

    private function createAdminUser(ObjectManager $manager): void
    {
        $primaryAdmin = new User();
        $primaryAdmin->setUsername('admin@onins.com');
        $primaryAdmin->setEmail('admin@onins.com');
        $primaryAdmin->setFullName('Administrator');
        $primaryAdmin->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $primaryAdmin->setPassword($this->passwordHasher->hashPassword($primaryAdmin, 'admin123'));
        $primaryAdmin->setIsActive(true);
        $primaryAdmin->setIsVerified(true);
        $manager->persist($primaryAdmin);

        $admin = new User();
        $admin->setUsername('stockadmin');
        $admin->setEmail('stockadmin@cabajon.com');
        $admin->setFullName('Stock Administrator');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setIsActive(true);
        $admin->setIsVerified(true);
        $manager->persist($admin);

        $staff = new User();
        $staff->setUsername('stockmanager');
        $staff->setEmail('stockmanager@cabajon.com');
        $staff->setFullName('Stock Manager');
        $staff->setRoles(['ROLE_STAFF']);
        $staff->setPassword($this->passwordHasher->hashPassword($staff, 'staff123'));
        $staff->setIsActive(true);
        $staff->setIsVerified(true);
        $manager->persist($staff);
    }

    private function createDemoCustomer(ObjectManager $manager): void
    {
        $customer = new User();
        $customer->setUsername('customer@onins.com');
        $customer->setEmail('customer@onins.com');
        $customer->setFullName('Demo Customer');
        $customer->setRoles(['ROLE_USER']);
        $customer->setPassword($this->passwordHasher->hashPassword($customer, 'customer123'));
        $customer->setIsActive(true);
        $customer->setIsVerified(true);
        $manager->persist($customer);
    }
}
