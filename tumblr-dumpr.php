#!/usr/bin/php
<?php
/**
 * Tumblr Dumpr
 * The ultimate command-line Tumblr photo dumper
 *
 * @author  Alan Hardman <alan@phpizza.com>
 */

// Place your key here
$consumer_key = '';



// Verify script is run from CLI
if(PHP_SAPI != 'cli') {
	exit('Must be run from command line.');
}

// Verify blog was specified
if(empty($argv[1])) {
	echo "tumblr-dumpr: dumps a Tumblr blog's images.\n";
	exit("Usage: php {$argv[0]} <blog> [posts=20] [consumer_key]");
}

// Set the initial limit
$limit = 20;

// Get limit and key from command line if given
if(!$consumer_key && isset($argv[3])) {
	$limit = $argv[2];
	$consumer_key = $argv[3];
} elseif(!$consumer_key && isset($argv[2]) && strlen($argv[2]) > 20) {
	$consumer_key = $argv[2];
}

// Verify keys are set
if(!$consumer_key) {
	exit("Consumer key is required. Edit the file and add your keys, or pass\n them via command line:\n"
			. "php {$argv[0]} <blog> [posts=20] [consumer_key]");
}

// Standardize blog name
$blog = $argv[1];
if(!strpos($blog, ".")) {
	$blog .= ".tumblr.com";
}

// Create blog name folder
if(!is_dir($blog)) {
	mkdir($blog);
}

// Some globals
$num_posts_seen = 0;
$imgs = array();
$base = "https://api.tumblr.com/v2/blog/{$blog}/posts/photo?api_key=" . urlencode($consumer_key);

// Run the API call loop
while($num_posts_seen < $limit) {
	$remaining = $limit - $num_posts_seen;

	$req = file_get_contents($base . "&limit=" . ($remaining > 20 ? 20 : $remaining) . "&offset={$num_posts_seen}");
	$obj = json_decode($req);

	if($obj->meta->status != 200) {
		exit($obj->meta->msg);
	}

	if($obj->response->total_posts < $limit) {
		$limit = $obj->response->total_posts;
	}

	echo "{$remaining} posts left.\n";

	foreach($obj->response->posts as $post) {
		$num_posts_seen++;
		foreach($post->photos as $photo) {
			$src = $photo->alt_sizes[0]->url;
			file_put_contents("$blog/" . basename($src), file_get_contents($src));
			$imgs[] = $src;
			usleep(100000);
		}
	}

	usleep(500000);

}
