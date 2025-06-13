USE ecommerce_db;

-- Insert super_admin user (password: Admin@123)
INSERT INTO users (username, password, email, full_name, phone, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'System Administrator', '+213123456789', 'super_admin');

-- Insert sample categories
INSERT INTO categories (name_en, name_fr, name_ar, slug, description_en, description_fr, description_ar) VALUES
('Men\'s Clothing', 'Vêtements Homme', 'ملابس الرجال', 'mens-clothing', 'Men\'s fashion collection', 'Collection de mode pour hommes', 'مجموعة أزياء الرجال'),
('Women\'s Clothing', 'Vêtements Femme', 'ملابس النساء', 'womens-clothing', 'Women\'s fashion collection', 'Collection de mode pour femmes', 'مجموعة أزياء النساء'),
('Accessories', 'Accessoires', 'اكسسوارات', 'accessories', 'Fashion accessories', 'Accessoires de mode', 'اكسسوارات الموضة');

-- Insert sample products
INSERT INTO products (category_id, name_en, name_fr, name_ar, description_en, description_fr, description_ar, price, sku, is_active) VALUES
(1, 'Classic White T-Shirt', 'T-Shirt Blanc Classique', 'تي شيرت أبيض كلاسيكي', 'Premium cotton t-shirt', 'T-shirt en coton premium', 'تي شيرت قطن فاخر', 29.99, 'TS-WHT-001', TRUE),
(2, 'Summer Dress', 'Robe d\'Été', 'فستان صيفي', 'Light and comfortable summer dress', 'Robe d\'été légère et confortable', 'فستان صيفي خفيف ومريح', 49.99, 'DR-SUM-001', TRUE),
(3, 'Leather Belt', 'Ceinture en Cuir', 'حزام جلد', 'Genuine leather belt', 'Ceinture en cuir véritable', 'حزام جلد طبيعي', 39.99, 'BL-LTH-001', TRUE);

-- Insert product sizes
INSERT INTO product_sizes (product_id, size, quantity) VALUES
(1, 'S', 50),
(1, 'M', 50),
(1, 'L', 50),
(1, 'XL', 50),
(2, 'S', 30),
(2, 'M', 30),
(2, 'L', 30),
(3, 'M', 20),
(3, 'L', 20);

-- Insert product images
INSERT INTO product_images (product_id, image_path, is_primary) VALUES
(1, 'products/tshirt-white-1.jpg', TRUE),
(1, 'products/tshirt-white-2.jpg', FALSE),
(2, 'products/summer-dress-1.jpg', TRUE),
(2, 'products/summer-dress-2.jpg', FALSE),
(3, 'products/leather-belt-1.jpg', TRUE);

-- Insert Algerian Wilayas
INSERT INTO delivery_cities (wilaya_code, wilaya_name_en, wilaya_name_fr, wilaya_name_ar, home_fee, desk_fee) VALUES
(1, 'Adrar', 'Adrar', 'أدرار', 800.00, 400.00),
(2, 'Chlef', 'Chlef', 'الشلف', 600.00, 300.00),
(3, 'Laghouat', 'Laghouat', 'الأغواط', 700.00, 350.00),
(4, 'Oum El Bouaghi', 'Oum El Bouaghi', 'أم البواقي', 650.00, 325.00),
(5, 'Batna', 'Batna', 'باتنة', 600.00, 300.00),
(6, 'Béjaïa', 'Béjaïa', 'بجاية', 550.00, 275.00),
(7, 'Biskra', 'Biskra', 'بسكرة', 650.00, 325.00),
(8, 'Béchar', 'Béchar', 'بشار', 800.00, 400.00),
(9, 'Blida', 'Blida', 'البليدة', 500.00, 250.00),
(10, 'Bouira', 'Bouira', 'البويرة', 550.00, 275.00),
(11, 'Tamanrasset', 'Tamanrasset', 'تمنراست', 900.00, 450.00),
(12, 'Tébessa', 'Tébessa', 'تبسة', 700.00, 350.00),
(13, 'Tlemcen', 'Tlemcen', 'تلمسان', 600.00, 300.00),
(14, 'Tiaret', 'Tiaret', 'تيارت', 650.00, 325.00),
(15, 'Tizi Ouzou', 'Tizi Ouzou', 'تيزي وزو', 550.00, 275.00),
(16, 'Alger', 'Alger', 'الجزائر', 500.00, 250.00),
(17, 'Djelfa', 'Djelfa', 'الجلفة', 650.00, 325.00),
(18, 'Jijel', 'Jijel', 'جيجل', 550.00, 275.00),
(19, 'Sétif', 'Sétif', 'سطيف', 600.00, 300.00),
(20, 'Saïda', 'Saïda', 'سعيدة', 650.00, 325.00),
(21, 'Skikda', 'Skikda', 'سكيكدة', 550.00, 275.00),
(22, 'Sidi Bel Abbès', 'Sidi Bel Abbès', 'سيدي بلعباس', 600.00, 300.00),
(23, 'Annaba', 'Annaba', 'عنابة', 550.00, 275.00),
(24, 'Guelma', 'Guelma', 'قالمة', 600.00, 300.00),
(25, 'Constantine', 'Constantine', 'قسنطينة', 550.00, 275.00),
(26, 'Médéa', 'Médéa', 'المدية', 550.00, 275.00),
(27, 'Mostaganem', 'Mostaganem', 'مستغانم', 550.00, 275.00),
(28, 'M\'Sila', 'M\'Sila', 'المسيلة', 600.00, 300.00),
(29, 'Mascara', 'Mascara', 'معسكر', 600.00, 300.00),
(30, 'Ouargla', 'Ouargla', 'ورقلة', 700.00, 350.00),
(31, 'Oran', 'Oran', 'وهران', 550.00, 275.00),
(32, 'El Bayadh', 'El Bayadh', 'البيض', 700.00, 350.00),
(33, 'Illizi', 'Illizi', 'إليزي', 900.00, 450.00),
(34, 'Bordj Bou Arréridj', 'Bordj Bou Arréridj', 'برج بوعريريج', 600.00, 300.00),
(35, 'Boumerdès', 'Boumerdès', 'بومرداس', 550.00, 275.00),
(36, 'El Tarf', 'El Tarf', 'الطارف', 550.00, 275.00),
(37, 'Tindouf', 'Tindouf', 'تندوف', 900.00, 450.00),
(38, 'Tissemsilt', 'Tissemsilt', 'تيسمسيلت', 600.00, 300.00),
(39, 'El Oued', 'El Oued', 'الوادي', 700.00, 350.00),
(40, 'Khenchela', 'Khenchela', 'خنشلة', 650.00, 325.00),
(41, 'Souk Ahras', 'Souk Ahras', 'سوق أهراس', 600.00, 300.00),
(42, 'Tipaza', 'Tipaza', 'تيبازة', 550.00, 275.00),
(43, 'Mila', 'Mila', 'ميلة', 600.00, 300.00),
(44, 'Aïn Defla', 'Aïn Defla', 'عين الدفلى', 550.00, 275.00),
(45, 'Naâma', 'Naâma', 'النعامة', 700.00, 350.00),
(46, 'Aïn Témouchent', 'Aïn Témouchent', 'عين تموشنت', 600.00, 300.00),
(47, 'Ghardaïa', 'Ghardaïa', 'غرداية', 700.00, 350.00),
(48, 'Relizane', 'Relizane', 'غليزان', 600.00, 300.00),
(49, 'Timimoun', 'Timimoun', 'تيميمون', 800.00, 400.00),
(50, 'Bordj Badji Mokhtar', 'Bordj Badji Mokhtar', 'برج باجي مختار', 900.00, 450.00),
(51, 'Ouled Djellal', 'Ouled Djellal', 'أولاد جلال', 700.00, 350.00),
(52, 'Béni Abbès', 'Béni Abbès', 'بني عباس', 800.00, 400.00),
(53, 'In Salah', 'In Salah', 'عين صالح', 900.00, 450.00),
(54, 'In Guezzam', 'In Guezzam', 'عين قزام', 900.00, 450.00),
(55, 'Touggourt', 'Touggourt', 'تقرت', 700.00, 350.00),
(56, 'Djanet', 'Djanet', 'جانت', 900.00, 450.00),
(57, 'El M\'Ghair', 'El M\'Ghair', 'المغير', 700.00, 350.00),
(58, 'El Meniaa', 'El Meniaa', 'المنيعة', 700.00, 350.00);

-- Insert sample delivery desks
INSERT INTO delivery_desks (wilaya_id, desk_name_en, desk_name_fr, desk_name_ar, address_en, address_fr, address_ar) VALUES
(16, 'Alger Center Desk', 'Bureau Centre d\'Alger', 'مكتب وسط الجزائر', '123 Main Street, Algiers', '123 Rue Principale, Alger', '123 شارع الرئيسي، الجزائر'),
(31, 'Oran Port Desk', 'Bureau Port d\'Oran', 'مكتب ميناء وهران', '45 Harbor Road, Oran', '45 Route du Port, Oran', '45 طريق الميناء، وهران'),
(25, 'Constantine University Desk', 'Bureau Université de Constantine', 'مكتب جامعة قسنطينة', '78 University Avenue, Constantine', '78 Avenue de l\'Université, Constantine', '78 شارع الجامعة، قسنطينة');

-- Insert sample coupon
INSERT INTO coupons (code, discount_type, discount_value, min_order_amount, max_uses, valid_from, valid_until, is_active) VALUES
('WELCOME10', 'percentage', 10.00, 100.00, 1000, '2024-01-01 00:00:00', '2024-12-31 23:59:59', TRUE); 