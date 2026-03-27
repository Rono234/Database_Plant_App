<?php
include('db_connection.php');

function escape($value)
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function resolvePlantImage($fileName)
{
	$name = trim((string) $fileName);
	if ($name === '') {
		return 'https://placehold.co/1200x600/F6E5E7/828C6A?text=Plant';
	}

	$baseName = pathinfo($name, PATHINFO_FILENAME);
	$hasExtension = pathinfo($name, PATHINFO_EXTENSION) !== '';

	$candidates = array($name);
	if (!$hasExtension && $baseName !== '') {
		$candidates[] = $baseName . '.jpg';
		$candidates[] = $baseName . '.jpeg';
		$candidates[] = $baseName . '.png';
	}

	

	foreach (array_unique($candidates) as $candidate) {
		$safeCandidate = basename($candidate);
		if (file_exists(__DIR__ . '/images/' . $safeCandidate)) {
			return 'images/' . $safeCandidate;
		}
	}

	return 'https://placehold.co/1200x600/F6E5E7/828C6A?text=' . rawurlencode($baseName ?: 'Plant');
}

function resolvePostImage($fileName)
{
	$name = trim((string) $fileName);
	if ($name === '') {
		return 'https://placehold.co/1200x600/F6E5E7/828C6A?text=Post';
	}

	$baseName = pathinfo($name, PATHINFO_FILENAME);
	$hasExtension = pathinfo($name, PATHINFO_EXTENSION) !== '';

	$candidates = array($name);
	if (!$hasExtension && $baseName !== '') {
		$candidates[] = $baseName . '.jpg';
		$candidates[] = $baseName . '.jpeg';
		$candidates[] = $baseName . '.png';
	}

	$folders = array('postImages/', 'images/');
	foreach ($folders as $folder) {
		$absoluteFolder = __DIR__ . '/' . $folder;
		foreach (array_unique($candidates) as $candidate) {
			$safeCandidate = basename($candidate);
			if (file_exists($absoluteFolder . $safeCandidate)) {
				return $folder . $safeCandidate;
			}
		}
	}

	return 'https://placehold.co/1200x600/F6E5E7/828C6A?text=' . rawurlencode($baseName ?: 'Post');
}

function getRatingSummary($con, $columnName, $itemId)
{
	$itemId = (int) $itemId;
	$sql = "SELECT ROUND(AVG(rating), 1) AS avg_rating, COUNT(rating) AS rating_count
		FROM comments
		WHERE $columnName = $itemId AND rating IS NOT NULL";

	$result = mysqli_query($con, $sql);
	if (!$result) {
		return array('avg_rating' => null, 'rating_count' => 0);
	}

	$row = mysqli_fetch_assoc($result);
	return array(
		'avg_rating' => $row && $row['avg_rating'] !== null ? (float) $row['avg_rating'] : null,
		'rating_count' => $row ? (int) $row['rating_count'] : 0
	);
}

$plantId = isset($_GET['plant_id']) ? (int) $_GET['plant_id'] : 0;
$postId = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;

$plant = null;
$post = null;
$comments = array();
$ratingSummary = array('avg_rating' => null, 'rating_count' => 0);
$pageType = '';
$errorMessage = '';

if ($plantId > 0) {
	$pageType = 'plant';

	$plantQuery = "SELECT
		p.plant_id,
		p.plant_name,
		p.plant_type,
		p.plant_desc,
		p.sun_level,
		p.season_id,
		p.difficulty,
		p.plant_img,
		s.start_plant,
		s.end_plant,
		GROUP_CONCAT(DISTINCT c.category_name ORDER BY c.category_name SEPARATOR ', ') AS categories,
		GROUP_CONCAT(DISTINCT pe.pest_name ORDER BY pe.pest_name SEPARATOR ', ') AS pests,
		CASE
		WHEN s.start_plant IN (12, 1, 2) THEN 'Winter'
		WHEN s.start_plant BETWEEN 3 and 5 THEN 'Spring'
		WHEN s.start_plant BETWEEN 6 and 8 THEN 'Summer'
		WHEN s.start_plant BETWEEN 9 and 11 THEN 'Fall'
		ELSE 'Unknown'
		END AS planting_season
	FROM plants AS p
	INNER JOIN seasons AS s ON p.season_id = s.season_id
	LEFT JOIN plants_categories AS pc ON p.plant_id = pc.plant_id
	LEFT JOIN categories AS c ON pc.category_id = c.category_id
	LEFT JOIN plants_pests AS pp ON p.plant_id = pp.plant_id
	LEFT JOIN pests AS pe ON pp.pest_id = pe.pest_id
	WHERE p.plant_id = $plantId
	GROUP BY
		p.plant_id,
		p.plant_name,
		p.plant_type,
		p.plant_desc,
		p.sun_level,
		p.season_id,
		p.difficulty,
		p.plant_img,
		s.start_plant,
		s.end_plant";

	$plantResult = mysqli_query($con, $plantQuery);
	if ($plantResult && mysqli_num_rows($plantResult) > 0) {
		$plant = mysqli_fetch_assoc($plantResult);
	} else {
		$errorMessage = 'Plant not found.';
	}

	$ratingSummary = getRatingSummary($con, 'plant_id', $plantId);

	$commentsQuery = "SELECT
		c.comment_text,
		c.rating,
		c.comment_date,
		u.user_name
	FROM comments AS c
	INNER JOIN users AS u ON c.user_id = u.user_id
	WHERE c.plant_id = $plantId
	ORDER BY c.comment_date DESC, c.comment_id DESC";

	$commentsResult = mysqli_query($con, $commentsQuery);
	if ($commentsResult) {
		while ($row = mysqli_fetch_assoc($commentsResult)) {
			$comments[] = $row;
		}
	}
} elseif ($postId > 0) {
	$pageType = 'post';

	$postQuery = "SELECT pt.post_id, pt.title, pt.body, pt.post_date, pt.post_img, u.user_name
	FROM posts AS pt
	INNER JOIN users AS u ON pt.user_id = u.user_id
	WHERE pt.post_id = $postId";

	$postResult = mysqli_query($con, $postQuery);
	if ($postResult && mysqli_num_rows($postResult) > 0) {
		$post = mysqli_fetch_assoc($postResult);
	} else {
		$errorMessage = 'Post not found.';
	}

	$ratingSummary = getRatingSummary($con, 'post_id', $postId);

	$commentsQuery = "SELECT
		c.comment_text,
		c.rating,
		c.comment_date,
		u.user_name
	FROM comments AS c
	INNER JOIN users AS u ON c.user_id = u.user_id
	WHERE c.post_id = $postId
	ORDER BY c.comment_date DESC, c.comment_id DESC";

	$commentsResult = mysqli_query($con, $commentsQuery);
	if ($commentsResult) {
		while ($row = mysqli_fetch_assoc($commentsResult)) {
			$comments[] = $row;
		}
	}
} else {
	$errorMessage = 'Select a plant or post card to view details.';
}
?>

<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Blossom Details</title>
	<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Cormorant+Garamond" rel="stylesheet">
	<link rel="stylesheet" href="style.css">
</head>

<body class="detail-page">
	<div class="detail-layout">
		<div class="header detail-header">
			<div class="header-top">
				<div class="logo">🌸 Blossom</div>
				<div class="tabs">
					<a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Plants</a>
					<a href="community.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'community.php' ? 'active' : ''; ?>">Community</a>
				</div>
			</div>
			<a class="back-link" href="<?php echo $pageType === 'post' ? 'community.php' : 'index.php'; ?>">← Back to <?php echo $pageType === 'post' ? 'Community' : 'Plants'; ?></a>
		</div>

		<?php if ($errorMessage !== '') { ?>
			<div class="empty-state"><?php echo escape($errorMessage); ?></div>
		<?php } else { ?>
			<section class="rating-summary">
				<h2>Average Collective Rating</h2>
				<p class="rating-value">
					<?php if ($ratingSummary['avg_rating'] !== null) { ?>
						<?php echo escape(number_format($ratingSummary['avg_rating'], 1)); ?>/5
					<?php } else { ?>
						No rating yet
					<?php } ?>
				</p>
				<p class="rating-count"><?php echo escape($ratingSummary['rating_count']); ?> total rating(s)</p>
			</section>

			<?php if ($pageType === 'plant' && $plant) { ?>
				<section class="detail-hero">
					<img src="<?php echo escape(resolvePlantImage($plant['plant_img'])); ?>" alt="<?php echo escape($plant['plant_name']); ?>">
					<div>
						<h1><?php echo escape($plant['plant_name']); ?></h1>
						<p><?php echo escape($plant['plant_desc']); ?></p>
						<div class="detail-pill-row">
							<span class="detail-pill">Category: <?php echo escape($plant['categories'] ?: 'None listed'); ?></span>
							<span class="detail-pill">Pests: <?php echo escape($plant['pests'] ?: 'None listed'); ?></span>
						</div>
					</div>
				</section>

				<section class="detail-table-wrap">
					<h2>Plant Data (its ugly ik ill make it prettier later)</h2>
					<table class="detail-table">
						<tr><th>plant name</th><td><?php echo escape($plant['plant_name']); ?></td></tr>
						<tr><th>plant type</th><td><?php echo escape($plant['plant_type']); ?></td></tr>
						<tr><th>plant description</th><td><?php echo escape($plant['plant_desc']); ?></td></tr>
						<tr><th>sun level</th><td><?php echo escape($plant['sun_level']); ?></td></tr>
						<tr><th>season</th><td><?php echo escape($plant['planting_season']); ?></td></tr>
						<tr><th>difficulty</th><td><?php echo escape($plant['difficulty']); ?></td></tr>
					</table>
				</section>
			<?php } ?>

			<?php if ($pageType === 'post' && $post) { ?>
				<section class="detail-hero">
					<img src="<?php echo escape(resolvePostImage($post['post_img'])); ?>" alt="<?php echo escape($post['title']); ?>">
					<div>
						<h1><?php echo escape($post['title']); ?></h1>
						<p><?php echo escape($post['body']); ?></p>
						<div class="detail-pill-row">
							<span class="detail-pill">Posted by: <?php echo escape($post['user_name']); ?></span>
							<span class="detail-pill">Date: <?php echo escape($post['post_date']); ?></span>
						</div>
					</div>
				</section>
			<?php } ?>

			<section class="comments-section">
				<h2>Comments and Individual Ratings</h2>
				<?php if (!empty($comments)) { ?>
					<?php foreach ($comments as $comment) { ?>
						<article class="comment-card">
							<div class="comment-top-row">
								<strong><?php echo escape($comment['user_name']); ?></strong>
								<span><?php echo escape($comment['comment_date']); ?></span>
							</div>
							<p><?php echo escape($comment['comment_text']); ?></p>
							<p class="individual-rating">Rating: <?php echo $comment['rating'] !== null ? escape($comment['rating']) . '/5' : 'Not rated'; ?></p>
						</article>
					<?php } ?>
				<?php } else { ?>
					<div class="empty-state">No comments yet for this <?php echo $pageType === 'post' ? 'post' : 'plant'; ?>.</div>
				<?php } ?>
			</section>
		<?php } ?>
	</div>
</body>

</html>
