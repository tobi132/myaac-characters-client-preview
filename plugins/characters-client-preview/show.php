<?php

(include __DIR__ . '/config.php') || (include __DIR__ . '/config.php.dist');

$configEqShower = config('characters-client-preview');

if (!getBoolean($configEqShower['enabled'])) {
	return;
}

global $player;
if(!isset($player) || !$player->isLoaded() || $player->isDeleted()) {
	echo 'Player not found.';
	return;
}

// Item image server
$loadOutfits = config('characters-client-preview')['outfits'];
$imageServer = config('item_images_url');
$imageType = config('item_images_extension') ?? 'gif';
$outfitServer = config('outfit_images_url');

$PEQ = $db->query("
	SELECT
		`player_id`,
		`pid`,
		`itemtype`,
		`count`
	FROM `player_items`
	WHERE `player_id`={$player->getId()}
	AND `pid`<'11'
");

$soulStamina = ($db->hasColumn('players', 'skill_fist'))
	? " `soul`, `stamina`,"
	: " `p`.`soul`, `p`.`stamina`,";

if (config('client') < 780) {
	$soulStamina = " 0 AS `soul`, 0 AS `stamina`,";
}

$player_query = ($db->hasColumn('players', 'skill_fist'))
	? /* true */ "SELECT
			`health`, `healthmax`,
			`mana`, `manamax`,
			`cap`,
			`experience`, `level`,
			{$soulStamina}
			`maglevel`,
			`skill_fist`,
			`skill_club`,
			`skill_sword`,
			`skill_axe`,
			`skill_dist`,
			`skill_shielding`,
			`skill_fishing`
		FROM `players`
		WHERE `id`={$player->getId()}
		LIMIT 1;"
	: /* false */ "SELECT
			`p`.`health`, `p`.`healthmax`,
			`p`.`mana`, `p`.`manamax`,
			`p`.`cap`,
			`p`.`experience`, `p`.`level`,
			{$soulStamina}
			`p`.`maglevel`,
			`fist`.`value` AS `skill_fist`,
			`club`.`value` AS `skill_club`,
			`sword`.`value` AS `skill_sword`,
			`axe`.`value` AS `skill_axe`,
			`dist`.`value` AS `skill_dist`,
			`shield`.`value` AS `skill_shielding`,
			`fish`.`value` AS `skill_fishing`
		FROM `players` AS `p`
		LEFT JOIN `player_skills` AS `fist` ON `p`.`id` = `fist`.`player_id` AND `fist`.`skillid` = 0
		LEFT JOIN `player_skills` AS `club` ON `p`.`id` = `club`.`player_id` AND `club`.`skillid` = 1
		LEFT JOIN `player_skills` AS `sword` ON `p`.`id` = `sword`.`player_id` AND `sword`.`skillid` = 2
		LEFT JOIN `player_skills` AS `axe` ON `p`.`id` = `axe`.`player_id` AND `axe`.`skillid` = 3
		LEFT JOIN `player_skills` AS `dist` ON `p`.`id` = `dist`.`player_id` AND `dist`.`skillid` = 4
		LEFT JOIN `player_skills` AS `shield` ON `p`.`id` = `shield`.`player_id` AND `shield`.`skillid` = 5
		LEFT JOIN `player_skills` AS `fish` ON `p`.`id` = `fish`.`player_id` AND `fish`.`skillid` = 6
		WHERE `p`.`id`= {$player->getId()}
		LIMIT 1;";

$playerstats = $db->query($player_query);
if (!$playerstats->rowCount()) {
	echo 'player not found';
	return;
}

$playerstats = $playerstats->fetch(PDO::FETCH_ASSOC);

$playerstats['experience'] = number_format($playerstats['experience'],0,'',',');
$playerstats['stamina'] = number_format($playerstats['stamina']/60,2,':','');

$bar_length = 100;
$bar_health = (int)($bar_length * ($playerstats['health'] / $playerstats['healthmax']));
if ($playerstats['manamax'] > 0) {
	$bar_mana = (int)($bar_length * ($playerstats['mana'] / $playerstats['manamax']));
}
else {
	$bar_mana = 100;
}

$male_outfits = $configEqShower['male_outfits'];
$female_outfits = $configEqShower['female_outfits'];

$featured_outfits = ($player->getSex() == 1) ? $male_outfits : $female_outfits;
$outfit_list = array();
$outfit_rows = COUNT($featured_outfits);
$outfit_columns = COUNT($featured_outfits[0]);

foreach ($featured_outfits as $row) {
	if (COUNT($row) > $outfit_columns) {
		$outfit_columns = COUNT($row);
	}
	foreach ($row as $column) {
		$outfit_list[] = $column;
	}
}

$outfit_storage = 10001000;
$highest_outfit_id = max($outfit_list);
$outfit_storage_max = $outfit_storage + $highest_outfit_id + 1;

$player_outfits = array();
$storage_sql = $db->query("
	SELECT `key`, `value`
	FROM `player_storage`
	WHERE `player_id`={$player->getId()}
	AND `key` > {$outfit_storage}
	AND `key` < {$outfit_storage_max}
");

$aquired_outfits = array();
if ($storage_sql->rowCount()) {
	foreach ($storage_sql as $row) {
		$outfit_id = $row['value'] >> 16;
		$aquired_outfits[$outfit_id] = true;
	}
}

?>
<table style="width: 100%">
	<tr>
		<td id="piv">
			<div id="piv_flex">
				<?php if ($configEqShower['equipment']): ?>
					<div id="piv_i">
						<img class="bg" src="<?php echo BASE_URL; ?>plugins/characters-client-preview/img/outfit.png">
						<div id="piv_lifebar"></div><div id="piv_lifetext"><span><?php echo $playerstats['health']; ?></span></div>
						<div id="piv_manabar"></div><div id="piv_manatext"><span><?php echo $playerstats['mana']; ?></span></div>
						<?php if ($PEQ !== false && !empty($PEQ)): foreach($PEQ as $item): ?>
							<div class="itm itm-<?php echo $item['pid']; ?>">
								<img src="<?php echo "{$imageServer}/".$item['itemtype'].".{$imageType}"; ?>">
							</div>
						<?php endforeach; endif; ?>
						<span id="piv_cap">Cap:<br><?php echo $playerstats['cap']; ?></span>
						<?php if ($loadOutfits): ?>
							<div class="inventory_outfit">
								<img src="<?php echo $outfitServer; ?>?id=<?php echo
								$player->getLookType(); ?>&addons=<?php echo $player->getLookAddons();
								?>&head=<?php	echo $player->getLookHead(); ?>&body=<?php echo $player->getLookBody();
								?>&legs=<?php echo $player->getLookLegs(); ?>&feet=<?php echo $player->getLookFeet(); ?>"
									 alt="img">
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ($configEqShower['skills']): ?>
					<div id="piv_s">
						<img class="bg" src="<?php echo BASE_URL; ?>plugins/characters-client-preview/img/skillsbackground.png">
						<span id="s_exp" class="txt"><?php echo $playerstats['experience']; ?></span>
						<span id="s_lvl" class="txt"><?php echo $playerstats['level']; ?></span>
						<span id="s_hp" class="txt"><?php echo number_format($playerstats['health'],0,'',','); ?></span>
						<span id="s_mp" class="txt"><?php echo number_format($playerstats['mana'],0,'',','); ?></span>
						<span id="s_soul" class="txt"><?php echo $playerstats['soul']; ?></span>
						<span id="s_cap" class="txt"><?php echo number_format($playerstats['cap'],0,'',','); ?></span>
						<span id="s_stamina" class="txt"><?php echo $playerstats['stamina']; ?></span>
						<span id="s_maglevel" class="txt"><?php echo $playerstats['maglevel']; ?></span>
						<span id="s_skill_fist" class="txt"><?php echo $playerstats['skill_fist']; ?></span>
						<span id="s_skill_club" class="txt"><?php echo $playerstats['skill_club']; ?></span>
						<span id="s_skill_sword" class="txt"><?php echo $playerstats['skill_sword']; ?></span>
						<span id="s_skill_axe" class="txt"><?php echo $playerstats['skill_axe']; ?></span>
						<span id="s_skill_dist" class="txt"><?php echo $playerstats['skill_dist']; ?></span>
						<span id="s_skill_shielding" class="txt"><?php echo $playerstats['skill_shielding']; ?></span>
						<span id="s_skill_fishing" class="txt"><?php echo $playerstats['skill_fishing']; ?></span>
					</div>
				<?php endif; ?>

				<?php if ($configEqShower['outfits']): ?>
					<div id="piv_o">
						<div class="bg">
							<div class="bg_t">
								<div class="t_m"></div>
								<div class="t_l"></div>
								<div class="t_r"></div>
							</div>
							<div class="bg_m">
								<div class="m_l"></div>
								<div class="m_m"></div>
								<div class="m_r"></div>
							</div>
							<div class="bg_b">
								<div class="b_m"></div>
								<div class="b_l"></div>
								<div class="b_r"></div>
							</div>
						</div>
						<div id="piv_o_container">
							<?php foreach ($featured_outfits as $row): foreach($row as $outfit_id): $g = (isset($aquired_outfits[$outfit_id])) ? "" : "grayimg"; ?>
								<img class="o <?php echo $g; ?>" src="<?php echo $outfitServer . "?id=" . $outfit_id;
								?>&addons=3&head=0&body=0&legs=0&feet=0">
							<?php endforeach; endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</td>
	</tr>
</table>

<style>
	/* Outfit column positions */
	<?php for ($column = 1; $column <= $outfit_columns; $column++): ?>
	#piv_o_container .o:nth-child(<?php echo $outfit_columns.'n+'.$column;?>) { right: <?php echo 10 + 40 * ($outfit_columns-$column); ?>px; }
	<?php endfor; ?>

	/* Outfit row positions */
	<?php for ($row = 1; $row <= $outfit_rows; $row++): ?>
	#piv_o_container .o:nth-child(n+<?php echo $outfit_columns * ($row-1)+1; ?>):nth-child(-n+<?php echo $outfit_columns*$row; ?>) { bottom: <?php echo 10 + 33 * ($outfit_rows-$row); ?>px; }
	<?php endfor; ?>
</style>

<?php $twig->display('characters-client-preview/style.html.twig', [
	'outfitColumns' => $outfit_columns,
	'outfitRows' => $outfit_rows,
	'barHealth' => $bar_health,
	'barMana' => $bar_mana,
	'barLength' => $bar_length,
]);
