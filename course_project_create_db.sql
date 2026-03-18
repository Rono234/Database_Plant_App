-- create database
DROP DATABASE IF EXISTS course_project;
CREATE DATABASE course_project;

-- select database
USE course_project;

-- create tables
CREATE TABLE categories
(
	category_id		INT			AUTO_INCREMENT NOT NULL,
    category_name	VARCHAR(50)	NOT NULL,
	CONSTRAINT	categories_pk
		PRIMARY KEY (category_id)
);

CREATE TABLE plants
(
	plant_id		INT			AUTO_INCREMENT NOT NULL,
    plant_name		VARCHAR(50)	NOT NULL,
    plant_type		VARCHAR(50)	NOT NULL,
    plant_desc		VARCHAR(60)	NOT NULL,
    sun_level		VARCHAR(50)	NOT NULL,
    start_plant		INT		NOT NULL,
    end_plant		INT		NOT NULL,
    difficulty		VARCHAR(30)	NOT NULL,
    plant_img		VARCHAR(50)	NOT NULL,
    CONSTRAINT plants_pk
		PRIMARY KEY (plant_id)
);

CREATE TABLE plants_categories
(
	plant_id		INT			NOT NULL,
    category_id		INT			NOT NULL,
    CONSTRAINT pc_plants_fk
		FOREIGN KEY (plant_id) REFERENCES plants (plant_id),
	CONSTRAINT pc_categories_fk
		FOREIGN KEY (category_id) REFERENCES categories (category_id)
);

CREATE TABLE pests
(
	pest_id			INT			AUTO_INCREMENT NOT NULL,
    pest_name		VARCHAR(50)	NOT NULL,
    pest_type		VARCHAR(50)	NOT NULL,
    CONSTRAINT pests_pk
		PRIMARY KEY (pest_id)
);

CREATE TABLE plants_pests
(
	plant_id		INT			NOT NULL,
    pest_id			INT			NOT NULL,
    CONSTRAINT pp_plants_fk
		FOREIGN KEY (plant_id) REFERENCES plants (plant_id),
	CONSTRAINT pp_pests_fk
		FOREIGN KEY (pest_id) REFERENCES pests (pest_id)
);

CREATE TABLE users
(
	user_id			INT			AUTO_INCREMENT NOT NULL,
    user_name		VARCHAR(50)	NOT NULL,
    CONSTRAINT	users_pk
		PRIMARY KEY (user_id)
);

CREATE TABLE posts
(
	post_id			INT				AUTO_INCREMENT NOT NULL,
    title 			VARCHAR(60)		NOT NULL,
    body			VARCHAR(255)	NOT NULL,
    user_id			INT				NOT NULL,
    post_date		DATE			NOT NULL,
    post_img		VARCHAR(50),
    CONSTRAINT posts_pk
		PRIMARY KEY (post_id),
	CONSTRAINT posts_users_fk
		FOREIGN KEY (user_id) REFERENCES users (user_id)
);

CREATE TABLE comments
(
	comment_id		INT				AUTO_INCREMENT NOT NULL,
    comment_text	VARCHAR(255)	NOT NULL,
    rating			INT,
    comment_date	DATE			NOT NULL,
    plant_id		INT,
    post_id			INT,
    user_id			INT				NOT NULL,
    CONSTRAINT comments_pk
		PRIMARY KEY (comment_id),
	CONSTRAINT comments_plants_fk
		FOREIGN KEY (plant_id) REFERENCES plants (plant_id),
	CONSTRAINT comments_posts_fk
		FOREIGN KEY (post_id) REFERENCES posts (post_id),
	CONSTRAINT comments_users_fk
		FOREIGN KEY (user_id) REFERENCES users (user_id)
);

-- insert rows into tables
INSERT INTO categories VALUES
(1,'Flower'),
(2,'Herb'),
(3,'Fern'),
(4,'Shrub'),
(5,'Edible'),
(6,'Pollinator-Friendly'),
(7,'Groundcover'),
(8,'Medicinal'),
(9,'Container-Friendly'),
(10,'Fragrant');

INSERT INTO plants VALUES
(1,"Hydrangea","Perennial","Showy flowering shrub with large mophead blooms.","Partial Shade to Full Sun",3,11,"Hard","hydrangea.jpg"),
(2,"Tomato","Perennial","Popular garden fruit; requires staking and water.","Full Sun",5,9,"Easy","tomato.jpg"),
(3,"Horsetail","Perennial","Unique, reed-like perennial that thrives in wet soil.","Full Sun to Partial Shade",3,10,"Medium","horsetail.jpg"),
(4,"Royal Fern","Perennial","Large, elegant fern with bright green fronds.","Partial Shade to Full Shade",4,10,"Medium","royalfern.jpg"),
(5,"Rose","Perennial","Classic flowering shrub known for fragrance.","Full Sun",3,11,"Medium","rose.jpg"),
(6,"Bleeding Heart","Perennial","Shade-loving perennial with heart-shaped flowers.","Partial Shade to Full Shade",4,7,"Hard","bleedingheart.jpg"),
(7,"Peony","Perennial","Long-lived perennial with massive spring blooms.","Full Sun",9,11,"Easy","peony.jpg"),
(8,"Coneflower","Perennial","Hardy wildflower with daisy-like petals.","Full Sun",4,10,"Easy ","coneflower.jpg"),
(9,"Daylily","Perennial","Extremely tough perennial with daily blooms.","Full Sun to Partial Shade",4,9,"Medium","daylily.jpg"),
(10,"Petunia","Annual","Prolific annual known for vibrant flowers.","Full Sun",5,10,"Easy","petunia.jpg"),
(11,"Sunflower","Annual","Tall, fast-growing annual with iconic heads.","Full Sun",5,9,"Easy","sunflower.jpg"),
(12,"Zinnia","Annual","Easy-to-grow annual with colorful heads.","Full Sun",5,10,"Easy","zinnia.jpg"),
(13,"Marigold","Annual","Hardy annual that helps repel garden pests.","Full Sun",5,10,"Easy","marigold.jpg"),
(14,"Rosemary","Perennial","Woody perennial herb for culinary and aromatic use.","Full Sun",4,11,"Easy","rosemary.jpg"),
(15,"Thyme","Perennial","Low-growing, versatile herb with flavorful leaves.","Full Sun",4,11,"Easy","thyme.jpg"),
(16,"Basil","Annual","Fragrant annual herb essential for Italian dishes.","Full Sun",5,9,"Easy","basil.jpg"),
(17,"Cilantro","Annual","Fast-growing herb used for leaves and seeds.","Partial Shade",3,10,"Easy","cilantro.jpg"),
(18,"Dill","Annual","Feathery annual herb used for pickling.","Full Sun",4,9,"Easy","dill.jpg"),
(19,"Azalea","Perennial","Acid-loving shrub with spectacular spring displays.","Partial Shade",3,11,"Hard","azalea.jpg"),
(20,"Ostrich Fern","Perennial","Large, upright fern resembling ostrich feathers.","Partial Shade to Full Shade",4,10,"Hard","ostrichfern.jpg"),
(21,"Boston Fern","Perennial","Classic indoor/outdoor fern with feathery fronds.","Partial Shade / Indirect",5,10,"Hard","bostonfern.jpg"),
(22,"Pepper","Annual","Heat-loving vegetable ranging from sweet to spicy.","Full Sun",5,9,"Medium","pepper.jpg"),
(23,"Squash","Annual","Vining or bush plant producing summer gourds.","Full Sun",5,9,"Easy","squash.jpg"),
(24,"Lettuce","Annual","Quick-growing leafy green for cool-weather salads.","Partial Shade to Full Sun",3,10,"Easy","lettuce.jpg"),
(25,"Maidenhair Fern","Perennial","Delicate fern with thin black stems and lacy leaves.","Partial Shade to Full Shade",4,10,"Medium","madenhairfern.jpg"),
(26,"Japanese Painted Fern","Perennial","Unique fern with silvery-grey and purple fronds.","Partial Shade to Full Shade",4,10,"Hard","japanesepaintedfern.jpg"),
(27,"Lilac","Perennial","Fragrant spring shrub with purple or white flowers.","Full Sun",3,11,"Easy","lilac.jpg"),
(28,"Blueberry","Perennial","Fruit-bearing shrub that requires acidic soil.","Full Sun",3,11,"Easy","blueberry.png"),
(29,"Juniper","Perennial","Evergreen conifer with needle-like leaves.","Full Sun",1,12,"Hard","juniper.jpg"),
(30,"Boxwood","Perennial","Dense evergreen shrub used for formal hedging.","Full Sun to Partial Shade",1,12,"Hard","boxwood.jpg"),
(31,"Sage","Perennial","Woody perennial herb with fuzzy, silver leaves.","Full Sun",4,11,"Medium","sage.jpg"),
(32,"Mint","Perennial","Vigorous, spreading herb with aromatic leaves.","Partial Shade to Full Sun",3,11,"Hard","mint.jpg"),
(33,"Lavender","Perennial","Fragrant purple spikes used for oils and tea.","Full Sun",4,11,"Easy","lavender.jpg"),
(34,"Oregano","Perennial","Pungent perennial herb for Mediterranean cooking.","Full Sun",4,11,"Easy","oregano.jpg"),
(35,"Chives","Perennial","Clumping herb with grass-like onion flavor.","Full Sun to Partial Shade",3,11,"Easy","chive.jpg"),
(36,"German Chamomile","Annual","Daisy-like annual used primarily for calming teas.","Full Sun",4,9,"Medium","germanchamomile.jpg"),
(37,"Calendula","Annual","Cheerful orange annual with medicinal petals.","Full Sun to Partial Shade",4,10,"Hard","calendula.jpg"),
(38,"Viola","Annual","Small, edible flowers that thrive in cool weather.","Partial Shade to Full Sun",3,11,"Hard","viola.jpg"),
(39,"Nasturtiam","Annual","Peppery edible flowers that trail or climb.","Full Sun",5,10,"Hard","nasturtiam.jpg"),
(40,"Parsley","Annual","Nutrient-rich herb used as a garnish or flavor.","Partial Shade to Full Sun",3,11,"Easy","parsley.jpg");

INSERT INTO plants_categories VALUES
(1,1),
(1,4),
(2,5),
(3,3),
(4,3),
(5,1),
(5,4),
(5,10),
(6,1),
(7,1),
(8,1),
(8,6),
(8,8),
(9,1),
(9,5),
(10,1),
(10,9),
(11,1),
(11,5),
(11,6),
(12,1),
(13,1),
(13,5),
(14,2),
(14,4),
(14,5),
(14,9),
(14,10),
(15,2),
(15,5),
(16,7),
(16,2),
(16,5),
(16,9),
(17,2),
(17,5),
(18,2),
(18,5),
(19,1),
(19,4),
(20,3),
(20,5),
(21,3),
(22,5),
(23,5),
(24,5),
(24,9),
(25,3),
(25,7),
(26,3),
(27,1),
(27,4),
(27,10),
(28,4),
(28,5),
(29,4),
(30,4),
(31,2),
(31,5),
(31,8),
(32,2),
(32,5),
(32,6),
(32,7),
(33,1),
(33,2),
(33,4),
(33,6),
(33,10),
(34,2),
(34,5),
(35,2),
(35,5),
(36,1),
(36,2),
(36,5),
(36,8),
(37,1),
(37,2),
(37,5),
(37,6),
(37,8),
(38,1),
(38,5),
(38,7),
(39,1),
(39,5),
(40,2),
(40,5),
(40,9);

INSERT INTO pests VALUES
(1,'Caterpillar','Harmful'),
(2,'Ladybug','Beneficial'),
(3,'Aphids','Harmful'),
(4,'Japanese Beetles','Harmful'),
(5,'Bees','Beneficial'),
(6,'Spider Mites','Harmful'),
(7,'Lacewings','Beneficial'),
(8,'Slugs','Harmful'),
(9,'Butterflies','Beneficial'),
(10,'Hoverflies','Beneficial');

INSERT INTO plants_pests VALUES
(1,1),
(1,2),
(1,3),
(2,2),
(2,3),
(2,6),
(5,2),
(5,3),
(5,4),
(8,5),
(8,9),
(11,5),
(11,1),
(16,2),
(16,3),
(33,5),
(33,9),
(39,1),
(40,1);

INSERT INTO users VALUES
(1,'Sumaya'),
(2,'Raneen'),
(3,'Izzy'),
(4,'Erma'),
(5,'Jean'),
(6,'Kris'),
(7,'Colette'),
(8,'Graig'),
(9,'Jocelyn'),
(10,'Weldon');

INSERT INTO posts VALUES
(1,"Check Out My Boxwood!!","This year I tried planting something new, rather than my usual herbs and edibles.",4,'2025-09-09','checkoutmyboxwood.jpg'),
(2,"Pests","I'm new to gardening and I wish someone had warned me about the amount of pests it attracts before I began!!",5,'2025-08-22',''),
(3,"Vegetables for Sale","If you're looking for some fresh grown vegetables for purchase, you've come to right place! Name your price in the comments!",7,'2025-07-31','vegetablesforsale.jpg'),
(4,"Army of Tomatoes","Guys, look at the amount of tomatoes I have. I might be set for life at this point LOL.",10,'2025-09-01','armyoftomatoes.jpg'),
(5,"Garden Takeover","Hey guysss, I'm taking over Raneen's garden today. Tempted to take a flower-",1,'2026-04-04','gardentakeover.jpg'),
(6,"Look at my Garden:","How does my garden look? I tried to make it as organized as possible!!",6,'2025-06-20','lookatmygarden.jpg'),
(7,"Recommendations?","Hello bloggers! I'm very new to gardening, what should I start with? Looking for something simple and easy to maintain.",8,'2025-07-03',''),
(8,"Humongous Squash","Have you ever seen a squash BIGGER than this!????",3,'2025-08-27','humongoussquash.jpg'),
(9,"Growing Rosemary from Seeds!","Growing Rosemary from seeds is said to be challenging so let's find out together!",9,'2025-05-02',''),
(10,"~So Many FlOwErS~","I LOVE FLOWERS!",2,'2026-03-17','somanyflowers.jpg');

INSERT INTO comments VALUES
(1,"Tomatoes are the best!",5,'2025-09-02',2,NULL,2),
(2,"I'm definitiely going to plant these in my garden!!",4,'2026-03-20',30,NULL,4),
(3,"Where did you get the seeds?",2,'2025-05-02',NULL,9,6),
(4,"Watch out for the bees!",5,'2026-03-18',NULL,10,7),
(5,"Is this plant a shrub or a fern?",4,'2025-09-11',NULL,1,9),
(6,"Do you have any food in your garden?",3,'2025-06-25',NULL,6,8),
(7,"I want a garden just like yours.",1,'2025-06-22',NULL,6,10),
(8,"What are those!?",1,'2025-06-20',30,NULL,3),
(9,"I hate pests.",2,'2025-08-22',NULL,2,1),
(10,"Woahhh, your squashes are really big!",5,'2025-08-31',NULL,8,5),
(11,"$5 for the squash would be great.",4,'2025-07-03',23,NULL,10);