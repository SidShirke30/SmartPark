USE smartpark_db;

-- Expanded Maharashtra parking network for SmartPark.
-- Coordinates are map points for the named locality/parking area and can be edited from Admin Parking.

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Shirdi','Temple Road','Shirdi Sai Smart Parking',140,52,'',35,19.7669,74.4774,'Shirdi','Covered',1,'+91 98220 11010'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Shirdi Sai Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Mumbai','Andheri East','Andheri Metro Smart Parking',220,88,'',60,19.1197,72.8468,'Mumbai','Multi-Level',1,'+91 98220 11011'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Andheri Metro Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Mumbai','Bandra West','Bandra Smart Parking Hub',190,64,'',65,19.0607,72.8362,'Mumbai','Multi-Level',1,'+91 98220 11012'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Bandra Smart Parking Hub');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Mumbai','Borivali West','Borivali Smart Parking',180,71,'',50,19.2307,72.8567,'Mumbai','Covered',0,'+91 98220 11013'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Borivali Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Mumbai','Colaba','Colaba Smart Parking',120,29,'',70,18.9220,72.8347,'Mumbai','Covered',0,'+91 98220 11014'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Colaba Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Mumbai','Kurla','Kurla Smart Parking',200,83,'',55,19.0726,72.8845,'Mumbai','Multi-Level',1,'+91 98220 11015'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Kurla Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Mumbai','BKC','BKC Smart Parking Arena',300,126,'',80,19.0670,72.8697,'Mumbai','Multi-Level',1,'+91 98220 11016'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='BKC Smart Parking Arena');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Thane','Naupada','Thane Naupada Smart Parking',170,61,'',50,19.1943,72.9660,'Thane','Multi-Level',1,'+91 98220 11017'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Thane Naupada Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Thane','Majiwada','Majiwada Smart Parking',230,104,'',55,19.2183,72.9781,'Thane','Covered',1,'+91 98220 11018'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Majiwada Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Thane','Ghodbunder Road','Ghodbunder Smart Parking',210,92,'',50,19.2715,72.9634,'Thane','Open Air',0,'+91 98220 11019'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Ghodbunder Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Kalyan','Station Road','Kalyan Smart Parking',150,48,'',40,19.2437,73.1355,'Kalyan','Covered',0,'+91 98220 11020'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Kalyan Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Dombivli','Phadke Road','Dombivli Smart Parking',140,42,'',40,19.2183,73.0868,'Dombivli','Open Air',0,'+91 98220 11021'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Dombivli Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Navi Mumbai','Vashi','Vashi Smart Parking',250,96,'',55,19.0771,72.9986,'Navi Mumbai','Multi-Level',1,'+91 98220 11022'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Vashi Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Navi Mumbai','Nerul','Nerul Smart Parking',180,72,'',45,19.0330,73.0169,'Navi Mumbai','Covered',1,'+91 98220 11023'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Nerul Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Panvel','Old Panvel','Panvel Smart Parking',160,57,'',35,18.9894,73.1175,'Panvel','Open Air',0,'+91 98220 11024'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Panvel Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Pune','Kothrud','Kothrud Smart Parking',180,69,'',40,18.5074,73.8077,'Pune','Covered',1,'+91 98220 11025'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Kothrud Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Pune','Baner','Baner Smart Parking',210,91,'',50,18.5590,73.7868,'Pune','Multi-Level',1,'+91 98220 11026'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Baner Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Pune','Hinjewadi','Hinjewadi Smart Parking',260,118,'',45,18.5912,73.7389,'Pune','Open Air',1,'+91 98220 11027'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Hinjewadi Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Pune','Hadapsar','Hadapsar Smart Parking',240,105,'',45,18.5089,73.9260,'Pune','Covered',1,'+91 98220 11028'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Hadapsar Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Pune','Viman Nagar','Viman Nagar Smart Parking',200,84,'',50,18.5679,73.9143,'Pune','Multi-Level',1,'+91 98220 11029'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Viman Nagar Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Pune','Swargate','Swargate Smart Parking',170,58,'',45,18.5018,73.8636,'Pune','Covered',0,'+91 98220 11030'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Swargate Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Pune','Wakad','Wakad Smart Parking',220,97,'',40,18.5993,73.7636,'Pune','Open Air',1,'+91 98220 11031'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Wakad Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Nashik','Panchavati','Panchavati Smart Parking',150,47,'',30,20.0119,73.7935,'Nashik','Open Air',0,'+91 98220 11032'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Panchavati Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Nashik','Gangapur Road','Gangapur Smart Parking',180,63,'',35,20.0060,73.7304,'Nashik','Covered',1,'+91 98220 11033'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Gangapur Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Nashik','CBS','Nashik CBS Smart Parking',160,51,'',30,20.0056,73.7793,'Nashik','Multi-Level',0,'+91 98220 11034'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Nashik CBS Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Nagpur','Dharampeth','Dharampeth Smart Parking',170,58,'',35,21.1360,79.0600,'Nagpur','Covered',1,'+91 98220 11035'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Dharampeth Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Nagpur','Wardha Road','Wardha Road Smart Parking',230,103,'',40,21.1100,79.0700,'Nagpur','Open Air',1,'+91 98220 11036'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Wardha Road Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Nagpur','Manish Nagar','Manish Nagar Smart Parking',150,52,'',30,21.1046,79.0752,'Nagpur','Covered',0,'+91 98220 11037'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Manish Nagar Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Chhatrapati Sambhajinagar','Railway Station Road','Sambhajinagar Station Parking',160,49,'',30,19.8763,75.3423,'Chhatrapati Sambhajinagar','Covered',0,'+91 98220 11038'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Sambhajinagar Station Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Chhatrapati Sambhajinagar','Garkheda','Garkheda Smart Parking',140,38,'',30,19.8622,75.3250,'Chhatrapati Sambhajinagar','Open Air',1,'+91 98220 11039'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Garkheda Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Chhatrapati Sambhajinagar','Waluj','Waluj Smart Parking',190,76,'',25,19.8340,75.2450,'Chhatrapati Sambhajinagar','Open Air',1,'+91 98220 11040'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Waluj Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Kolhapur','Tarabai Park','Tarabai Park Smart Parking',130,44,'',30,16.7113,74.2410,'Kolhapur','Covered',0,'+91 98220 11041'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Tarabai Park Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Kolhapur','Shahupuri','Shahupuri Smart Parking',120,31,'',25,16.7045,74.2437,'Kolhapur','Open Air',0,'+91 98220 11042'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Shahupuri Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Solapur','Hotgi Road','Hotgi Road Smart Parking',150,45,'',25,17.6460,75.9100,'Solapur','Open Air',0,'+91 98220 11043'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Hotgi Road Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Solapur','Saat Rasta','Saat Rasta Smart Parking',140,39,'',25,17.6683,75.9064,'Solapur','Covered',0,'+91 98220 11044'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Saat Rasta Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Satara','Powai Naka','Satara Smart Parking',120,37,'',25,17.6805,74.0183,'Satara','Open Air',0,'+91 98220 11045'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Satara Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Sangli','Miraj Road','Sangli Smart Parking',130,41,'',25,16.8524,74.5815,'Sangli','Covered',0,'+91 98220 11046'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Sangli Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Latur','Gandhi Chowk','Latur Smart Parking',110,34,'',25,18.4088,76.5604,'Latur','Open Air',0,'+91 98220 11047'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Latur Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Nanded','Vazirabad','Nanded Smart Parking',120,36,'',25,19.1503,77.3197,'Nanded','Covered',0,'+91 98220 11048'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Nanded Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Jalgaon','Station Road','Jalgaon Smart Parking',150,49,'',25,21.0077,75.5626,'Jalgaon','Open Air',0,'+91 98220 11049'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Jalgaon Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Akola','Civil Lines','Akola Smart Parking',110,29,'',25,20.7059,77.0011,'Akola','Covered',0,'+91 98220 11050'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Akola Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Amravati','Rajapeth','Amravati Smart Parking',130,43,'',25,20.9374,77.7796,'Amravati','Open Air',0,'+91 98220 11051'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Amravati Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Dhule','Deopur','Dhule Smart Parking',100,28,'',20,20.9042,74.7749,'Dhule','Open Air',0,'+91 98220 11052'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Dhule Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Malegaon','Camp Road','Malegaon Smart Parking',120,33,'',20,20.5579,74.5280,'Malegaon','Covered',0,'+91 98220 11053'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Malegaon Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Manmad','Station Road','Manmad Smart Parking',100,27,'',20,20.2530,74.4370,'Manmad','Open Air',0,'+91 98220 11054'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Manmad Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Bhusawal','Jalgaon Road','Bhusawal Smart Parking',130,39,'',20,21.0437,75.7850,'Bhusawal','Open Air',0,'+91 98220 11055'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Bhusawal Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Ratnagiri','Maruti Mandir Road','Ratnagiri Smart Parking',110,32,'',30,16.9944,73.3000,'Ratnagiri','Covered',0,'+91 98220 11056'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Ratnagiri Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Karad','Market Yard Road','Karad Smart Parking',100,31,'',25,17.2890,74.1810,'Karad','Open Air',0,'+91 98220 11057'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Karad Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Baramati','MIDC Road','Baramati Smart Parking',120,40,'',25,18.1517,74.5777,'Baramati','Open Air',1,'+91 98220 11058'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Baramati Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Ahmednagar','Delhi Gate','Ahmednagar City Smart Parking',130,42,'',25,19.0948,74.7480,'Ahmednagar','Covered',0,'+91 98220 11059'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Ahmednagar City Smart Parking');

INSERT INTO parkings(location,street,name,slot,remaining_slots,attendant,price,latitude,longitude,city,parking_type,ev_charging,contact_phone)
SELECT 'Shirdi','Pimpalwadi Road','Shirdi Temple Area Parking',180,66,'',45,19.7664,74.4774,'Shirdi','Open Air',0,'+91 98220 11060'
WHERE NOT EXISTS (SELECT 1 FROM parkings WHERE name='Shirdi Temple Area Parking');
