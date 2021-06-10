<?php
/**
 * Template Name: Main Page
 * Template Post Type: post, page
 */

get_header();
define('ID', get_the_ID());


$curr_season_id = get_option('sportspress_season', null);
$curr_season = get_term($curr_season_id)->name;

$curr_league_id = get_option('sportspress_league', null);


// Максимальное кол-ва матчей прошедших + текущик
$maxComplete = 4;


// Выбираем прошедшие и текушие
$matchesComplete = get_posts([
    'numberposts' => $maxComplete,
    'category' => 0,
    'orderby' => 'date',
    'order' => 'DESC',
    'post_status' => 'publish',
    'include' => [],
    'exclude' => [],
    'tax_query' => [
        // 'relation' => 'AND',
        [
            'taxonomy' => 'sp_season',
            'field' => 'id',
            'terms' => [$curr_season_id],
        ],
    ],
    'meta_query' => [
        'relation' => 'AND',
        [
            'key' => 'sp_team',
            'value' => MAIN_TEAM_ID,

        ],
    ],
    'post_type' => 'sp_event',
    'suppress_filters' => true, // подавление работы фильтров изменения SQL запроса
]);

$nowTimeMsk = current_time("timestamp"); // Текущее время по Мск
// dd(date('d.m.Y h:i:s'), $nowTimeMsk);
$calendarMatchMain = [];
$maxAutoTime = 110 * 60; // Максимальное время матча в авто режиме


foreach ($matchesComplete as $key => $matchComplete) {
    $calendarMatchMain[$key]["data"] = $matchComplete;

}

$calendarMatchMain = array_reverse($calendarMatchMain);


// Будущие матчи
$matchesFuture = get_posts([
    'numberposts' => $maxComplete + 1,
    'category' => 0,
    'orderby' => 'date',
    'order' => 'ASC',
    'post_status' => 'future',
    'include' => [],
    'exclude' => [],
    'tax_query' => [
        'relation' => 'AND',
        [
            'taxonomy' => 'sp_season',
            'field' => 'id',
            'terms' => [$curr_season_id],
        ],
    ],
    'meta_query' => [
        'relation' => 'AND',
        [
            'key' => 'sp_team',
            'value' => MAIN_TEAM_ID,

        ],
    ],
    'post_type' => 'sp_event',
    'suppress_filters' => true, // подавление работы фильтров изменения SQL запроса
]);

$count = $maxComplete;
foreach ($matchesFuture as $key => $matchFuture) {
    $calendarMatchMain[$count]["data"] = $matchFuture;
    $count++;
}

$args = [
    'post_type' => 'news',
    'posts_per_page' => 2,
    // 'cat' => 261,
    'tax_query' => [
        [
            'taxonomy' => 'news_categories',
            'field' => 'id',
            'terms' => [261, 291],
        ],
    ],
    'meta_query' => [
        [
            'key' => 'in_slider',
            'value' => true,
        ],
    ],
];
$news_with_match = new WP_Query($args);
$ids = [];
foreach ($news_with_match->posts as $item) {
    $ids[] = $item->ID;
}
$args2 = [
    'post_type' => 'news',
    'posts_per_page' => 5 - count($news_with_match),
    'meta_query' => [
        [
            'key' => 'in_slider',
            'value' => true,
        ],
    ],
    'tax_query' => [
        [
            'taxonomy' => 'news_categories',
            'field' => 'id',
            'terms' => [261, 291],
            'operator' => 'NOT IN',
        ],
    ],
    'post__not_in' => $ids,
];
$slider_news = (new WP_Query($args2))->posts;
// dd($slider_news);
$count = count($slider_news) + count($news_with_match);
?>


    <section class="index__main-head main-head main-head--no-overlay">
        <div class="main-head__container">
            <div class="js-slider js-slider--main main-head__slider swiper-container">
                <div class="swiper-wrapper main-head__slider-wrapper">
                    <? foreach ($news_with_match->posts as $slider_match) {
                        $img = get_field('izobrazhenie', $slider_match->ID) ? get_field('izobrazhenie', $slider_match->ID)['url'] : get_the_post_thumbnail_url($slider_match->ID, 'full');
                        $img = $img ? $img : get_template_directory_uri() . '/images/content/news_no_img.png';

                        $match_id = get_field('match', $slider_match->ID);
                        $match_obj = new SP_Event($match_id);
                        $match = $match_obj->post;
                        $link = get_field('buy_ticket', $match_id);
                        $teams = get_post_meta($match_id, 'sp_team');
                        $league = get_the_terms($match_id, 'sp_league');
                        $league_logo = get_field("izobrazhenie", "sp_league_".$league[0]->term_id)['sizes']['medium'];
                        $date = get_the_date('d F, H:i', $match_id);
                        $timer = [
                            'days' => 0,
                            'hours' => 0,
                            'minutes' => 0,
                            'seconds' => 0,
                        ];
                        $rem = strtotime($match->post_date) - $nowTimeMsk;
                        if ($rem < 0 && $count > 1) continue;
                        //  dd($match);

                        $day = floor($rem / 86400);
                        $hr = floor(($rem % 86400) / 3600);
                        $min = floor(($rem % 3600) / 60);
                        $sec = ($rem % 60);
                        if ($day) $timer['days'] = $day;
                        if ($hr) $timer['hours'] = $hr;
                        if ($min) $timer['minutes'] = $min;
                        if ($sec) $timer['seconds'] = $sec;
                        ?>
                        <div class="swiper-slide main-head__slide main-head-slide"
                             style="background-image: url('<?= $img ?>')">
                            <div class="main-head-slide__container">
                                <div class="main-head-slide__info-top match-with-time-left">
                                    <div class="match-with-time-left__how-play how-play-with-league">
                                        <div class="how-play-with-league__commands-icon-list main-head-slide__commands-icon-list commands-icon-list">
                                            <div class="commands-icon-list__item commands-icon">
                                                <?php if ($league_logo): ?>
                                                    <img class="commands-icon__img" src="<?= $league_logo ?>" alt="">
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="how-play-with-league__commands-icon-list main-head-slide__commands-icon-list commands-icon-list">
                                            <div class="commands-icon-list__item commands-icon">
                                                <?php if (get_the_post_thumbnail_url($teams[0], 'sportspress-fit-icon')): ?>
                                                    <img class="commands-icon__img"
                                                         src="<?= get_the_post_thumbnail_url($teams[0], 'sportspress-fit-icon') ?>"
                                                         alt="">
                                                <?php endif; ?>
                                            </div>
                                            <div class="commands-icon-list__item commands-icon">
                                                <?php if (get_the_post_thumbnail_url($teams[1], 'sportspress-fit-icon')): ?>
                                                    <img class="commands-icon__img"
                                                         src="<?= get_the_post_thumbnail_url($teams[1], 'sportspress-fit-icon') ?>"
                                                         alt="">
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($rem > 0): ?>
                                        <ul class="match-with-time-left__when-play time-left">
                                            <li class="time-left__item">
                                                <p class="time-left__item-value days"><?= $timer['days'] ?></p>
                                                <p class="time-left__item-key">дн</p>
                                            </li>
                                            <li class="time-left__item">
                                                <p class="time-left__item-value hours"><?= $timer['hours'] ?></p>
                                                <p class="time-left__item-key">ч</p>
                                            </li>
                                            <li class="time-left__item">
                                                <p class="time-left__item-value minutes"><?= $timer['minutes'] ?></p>
                                                <p class="time-left__item-key">мин</p>
                                            </li>
                                            <li class="time-left__item">
                                                <p class="time-left__item-value seconds"><?= $timer['seconds'] ?></p>
                                                <p class="time-left__item-key">сек</p>
                                            </li>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                                <div class="main-head-slide__content">
                                    <h2 class="main-head-slide__title custom-font custom-font--bold-italic">
                                        <a href="<?= get_permalink($slider_match->ID) ?>"><?= convert_quotes_to_typographic($slider_match->post_title) ?></a>
                                    </h2>
                                    <? if (get_field('tip_matcha', $match->ID) != 'Домашний') {?>
                                        <a class="buy-ticket more-details--slider" href="<?= get_permalink($slider_match->ID) ?>"><span>Подробнее</span></a>
                                    <? } else { ?>
                                        <?php
                                        $buy_ticket = get_field('buy_ticket', $match->ID);
                                        if ($buy_ticket != ''): ?>
                                            <a class="buy-ticket" href="<?= $link ?>"><span>Купить билет</span></a>
                                            <?php else: ?>
                                                <a class="buy-ticket more-details--slider" href="<?= get_permalink($slider_match->ID) ?>"><span>Подробнее</span></a>
                                        <?php endif; ?>
                                    <? } ?>
                                </div>
                            </div>
                        </div>
                    <? } ?>
                    <? foreach ($slider_news as $slider) {
                        $img = get_field('izobrazhenie', $slider->ID) ? get_field('izobrazhenie', $slider->ID)['url'] : get_the_post_thumbnail_url($slider->ID, 'full');
                        $img = $img ? $img : get_template_directory_uri() . '/images/content/news_no_img.png';
                        $link = get_permalink($slider->ID);
                        // $is_match = get_field('privyazat_k_matchu', $slider->ID);
                        $match_ID = get_field('match', $slider->ID);
                        dd(get_field('match', $slider->ID));
                        if($match_ID){
                            $match_obj = new SP_Event($match_ID);
                            $match = $match_obj->post;
                            $link = get_field('buy_ticket', $match_ID);
                            $teams = get_post_meta($match_ID, 'sp_team');
                            $league = get_the_terms($match_ID, 'sp_league');
                            $league_logo = get_field("izobrazhenie", "sp_league_".$league[0]->term_id)['sizes']['medium'];
                            $date = get_the_date('d F, H:i', $match_ID);
                            $timer = [
                                'days' => 0,
                                'hours' => 0,
                                'minutes' => 0,
                                'seconds' => 0,
                            ];
                            $rem = strtotime($match->post_date) - $nowTimeMsk;
                            // if ($rem < 0 && $count > 1) continue;
                            //  dd($match);

                            $day = floor($rem / 86400);
                            $hr = floor(($rem % 86400) / 3600);
                            $min = floor(($rem % 3600) / 60);
                            $sec = ($rem % 60);
                            if ($day) $timer['days'] = $day;
                            if ($hr) $timer['hours'] = $hr;
                            if ($min) $timer['minutes'] = $min;
                            if ($sec) $timer['seconds'] = $sec;
                        }
                        ?>
                        <div class="swiper-slide main-head__slide main-head-slide"
                             style="background-image: url('<?= $img ? $img : get_template_directory_uri() . '/images/content/news_no_img.png' ?>')">
                            <div class="main-head-slide__container">
                                <?php if($match_ID): ?>
                                    <div class="main-head-slide__info-top match-with-time-left">
                                    <div class="match-with-time-left__how-play how-play-with-league">
                                        <div class="how-play-with-league__commands-icon-list main-head-slide__commands-icon-list commands-icon-list">
                                            <div class="commands-icon-list__item commands-icon">
                                                <?php if ($league_logo): ?>
                                                    <img class="commands-icon__img" src="<?= $league_logo ?>" alt="">
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="how-play-with-league__commands-icon-list main-head-slide__commands-icon-list commands-icon-list">
                                            <div class="commands-icon-list__item commands-icon">
                                                <?php if (get_the_post_thumbnail_url($teams[0], 'sportspress-fit-icon')): ?>
                                                    <img class="commands-icon__img"
                                                         src="<?= get_the_post_thumbnail_url($teams[0], 'sportspress-fit-icon') ?>"
                                                         alt="">
                                                <?php endif; ?>
                                            </div>
                                            <div class="commands-icon-list__item commands-icon">
                                                <?php if (get_the_post_thumbnail_url($teams[1], 'sportspress-fit-icon')): ?>
                                                    <img class="commands-icon__img"
                                                         src="<?= get_the_post_thumbnail_url($teams[1], 'sportspress-fit-icon') ?>"
                                                         alt="">
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($rem > 0): ?>
                                        <ul class="match-with-time-left__when-play time-left">
                                            <li class="time-left__item">
                                                <p class="time-left__item-value days"><?= $timer['days'] ?></p>
                                                <p class="time-left__item-key">дн</p>
                                            </li>
                                            <li class="time-left__item">
                                                <p class="time-left__item-value hours"><?= $timer['hours'] ?></p>
                                                <p class="time-left__item-key">ч</p>
                                            </li>
                                            <li class="time-left__item">
                                                <p class="time-left__item-value minutes"><?= $timer['minutes'] ?></p>
                                                <p class="time-left__item-key">мин</p>
                                            </li>
                                            <li class="time-left__item">
                                                <p class="time-left__item-value seconds"><?= $timer['seconds'] ?></p>
                                                <p class="time-left__item-key">сек</p>
                                            </li>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                <div class="main-head-slide__content">
                                    <h2 class="main-head-slide__title custom-font custom-font--bold-italic"><?= convert_quotes_to_typographic($slider->post_title) ?></h2>
                                    <a class="buy-ticket more-details--slider" href="<?= $link ?>"><span>Подробнее</span></a>
                                </div>
                            </div>
                        </div>
                    <? }
                    wp_reset_postdata(); ?>
                </div>
                <div class="swiper-pagination main-head__pagination"></div>
            </div>

            <? if (count($calendarMatchMain) > 0) { ?>
                <div class="main-match-slider">
                    <div class="main-match-slider__line-text-wrap">
                        <div class="marquee">
                            <p class="main-match-slider__line-text marquee__inner custom-font custom-font--bold-italic">
                                ФК
                                «ОЛИМП-ДОЛГОПРУДНЫЙ»</p>
                        </div>
                        <div class="marquee">
                            <p class="main-match-slider__line-text marquee__inner custom-font custom-font--bold-italic">
                                ФК
                                «ОЛИМП-ДОЛГОПРУДНЫЙ»</p>
                        </div>
                    </div>
                    <div class="main-match-slider__navigation">
                        <div class="link-border swiper-button-prev main-match-slider__button-prev"><img
                                    class="link-border__img"
                                    src="<?= get_template_directory_uri() ?>/images/icon/arrow-prev.svg"
                                    alt="Предыдущий слайд"></div>
                        <div class="link-border swiper-button-next main-match-slider__button-next"><img
                                    class="link-border__img"
                                    src="<?= get_template_directory_uri() ?>/images/icon/arrow-next.svg"
                                    alt="Следующий слайд"></div>
                    </div>

                    <div class="main-head__match-sliders js-slider js-slider--match-list swiper-container main-match-slider__slider"
                         style="opacity: 0;">
                        <div class="swiper-wrapper main-match-slider__wrapper">
                            <? $index_comming = false;
                            $index_comming_int = 0;

                            ?>
                            <? foreach ($calendarMatchMain as $index => $eventMatch) {
                                $event = $eventMatch["data"];
                                $match = get_match($event->ID);
                                switch ($match->event_status) {
                                    case 'COMPLETE':
                                        $match->status = "Матч окончен";
                                        ?>
                                        <div class="swiper-slide main-match-slider__slide match-card match-card--past not-init"
                                             style="background-image: url('<?= $match->cart_bg ?>')"
                                             data-slide-id="<?= $index ?>">
                                            <div class="match-card__wrapper">
                                                <div class="match-card__header">
                                                    <div class="match-card__header-text match-date-when">
                                                        <p class="match-card__header-date match-date-when__date"><?= get_the_date('d.m', $match->ID) ?></p>
                                                        <p class="match-card__header-when match-date-when__when"><?= $match->status ?></p>
                                                    </div>
                                                    <div class="match-card__commands-list commands-icon-list">
                                                        <div class="match-card__commands-list-item commands-icon-list__item commands-icon"><?= $match->teams_logo[0] ?></div>
                                                        <div class="match-card__commands-list-item commands-icon-list__item commands-icon"><?= $match->teams_logo[1] ?></div>
                                                    </div>
                                                </div>

                                                <div class="match-card__league">
                                                    <?php if ($match->league_logo): ?>
                                                        <img class="match-card__league-img" src="<?= $match->league_logo ?>"
                                                             width="20"
                                                             alt="<?= $match->league[0]->name ?>">
                                                    <?php endif; ?>
                                                    <p class="match-card__league-name"><?= $match->league[0]->name ?></p>
                                                </div>
                                                <ul class="match-card__score-list">
                                                    <li class="match-card__score-item">
                                                        <p class="match-card__command-name <?= $match->teams_class[0] ?>"><?= $match->teams_short_name[0] ?></p>
                                                        <p class="match-card__score match-score-number <?= $match->result_class[0] ?>"><?= $match->result[0] ?></p>
                                                    </li>
                                                    <li class="match-card__score-item">
                                                        <p class="match-card__command-name <?= $match->teams_class[1] ?>"><?= $match->teams_short_name[1] ?></p>
                                                        <p class="match-card__score match-score-number <?= $match->result_class[1] ?>"><?= $match->result[1]  ?></p>
                                                    </li>
                                                </ul>
                                                <div class="match-card__record-more">
                                                    <div class="match-card__record">
                                                        <?= $match->cart_photo ?>
                                                        <?= $match->cart_video ?>
                                                    </div>
                                                    <a class="news-card__more more-details more-details--white match-card__more" href="<?= $match->link ?>">Подробнее</a>
                                                </div>
                                            </div>
                                        </div>
                                        <?
                                        break;
                                    case 'MATCH_PLAYIND':
                                        if (!$index_comming) {
                                            $index_comming = true;
                                            $index_comming_int = true;
                                        } else {
                                            $index_comming_int = false;
                                        }
                                        ?>
                                        <div class="swiper-slide main-match-slider__slide match-card match-card--live not-init"
                                             <? if ($index_comming_int){
                                             ?>data-comming="<?= $index_comming_int ?>" <?
                                        } ?> data-slide-id="<?= $index ?>">
                                            <div class="match-card__wrapper">
                                                <div class="match-card__header">
                                                    <div class="match-card__header-text match-date-when">
                                                        <p class="match-card__header-date match-date-when__date"><?= $match->status ?></p>
                                                        <!-- <p class="match-card__header-when match-date-when__when">Яндекс.Эфир</p> -->
                                                    </div>
                                                    <div class="match-card__commands-list commands-icon-list">
                                                        <div class="match-card__commands-list-item commands-icon-list__item commands-icon"><?= $match->teams_logo[0] ?></div>
                                                        <div class="match-card__commands-list-item commands-icon-list__item commands-icon"><?= $match->teams_logo[1] ?></div>
                                                    </div>
                                                </div>
                                                <div class="match-card__league"><img class="match-card__league-img"src="<?= $match->league_logo ?>" width="20" alt="<?= $match->league[0]->name ?>">
                                                    <p class="match-card__league-name"><?= $match->league[0]->name ?></p>
                                                </div>

                                                <div class="match-card__time-match-wrap">
                                                    <div class="match-card__bar" style="width: <?= $match->width_icon ?>%;"></div>
                                                    <p class="match-card__time-match <?= $match->timer_active ? 'active-timer' : '' ?>" data-id="<?= $match->ID ?>">
                                                    <span class="min" data-part="<?= $match->part ?>">
                                                        <?= $match->minute_past; ?>
                                                    </span>
                                                        :
                                                    <span class="sec">
                                                        <?= $match->second_past ?>
                                                    </span>
                                                    </p>
                                                    <p class="match-card__period-match"><?= $match->part ?></p>
                                                </div>

                                                <ul class="match-card__score-list">
                                                    <li class="match-card__score-item">
                                                        <p class="match-card__command-name <?= $match->teams_class[0] ?>"><?= $match->teams_logo[0] ?></p>
                                                        <p class="match-card__score match-score-number"><?= $match->result[0] ?></p>
                                                    </li>
                                                    <li class="match-card__score-item">
                                                        <p class="match-card__command-name <?= $match->teams_class[1] ?>"><?= $match->teams_logo[1] ?></p>
                                                        <p class="match-card__score match-score-number"><?= $match->result[1] ?></p>
                                                    </li>
                                                </ul>
                                                <div class="match-card__record-more">
                                                    <a class="news-card__more more-details" href="<?= $match->link ?>">Подробнее</a>
                                                </div>
                                            </div>
                                        </div>

                                        <?


                                        break;
                                    case 'FUTURE':

                                        if (!$index_comming) {
                                            $index_comming = true;
                                            $index_comming_int = true;
                                        } else {
                                            $index_comming_int = false;
                                        }
                                        ?>
                                        <div class="swiper-slide main-match-slider__slide match-card match-card--future not-init"
                                             <? if ($index_comming_int){
                                             ?>data-comming="<?= $index_comming_int ?>" <?
                                        } ?> data-slide-id="<?= $index ?>">
                                            <div class="match-card__wrapper">

                                                <a href="<?= $match->link ?>"
                                                   class="match-card__header__link"> </a>
                                                <div class="match-card__header">
                                                    <div class="match-card__header-text match-date-when">
                                                        <p class="match-card__header-date match-date-when__date"><?= get_the_date('H:i', $match->ID) ?></p>
                                                        <p class="match-card__header-when match-date-when__when"><?= get_the_date('j F', $match->ID) ?></p>
                                                    </div>
                                                    <div class="match-card__commands-list commands-icon-list">
                                                        <div class="match-card__commands-list-item commands-icon-list__item commands-icon"><?= $match->teams_logo[0] ?></div>
                                                        <div class="match-card__commands-list-item commands-icon-list__item commands-icon"><?= $match->teams_logo[1] ?></div>
                                                    </div>
                                                </div>
                                                <div class="match-card__league"><img class="match-card__league-img"
                                                                                     src="<?= $match->league_logo ?>" width="20"
                                                                                     alt="<?= $match->league[0]->name ?>">
                                                    <p class="match-card__league-name"><?= $match->league[0]->name ?></p>
                                                </div>
                                                <ul class="match-card__score-list">
                                                    <li class="match-card__score-item">
                                                        <p class="match-card__command-name <?= $match->teams_class[0] ?>"><?= $match->teams_short_name[0] ?></p>
                                                        <?php if($match->status == 'Технический результат'){ ?>
                                                            <p class="match-card__score match-score-number"><?= $match->result[0] ?></p>
                                                        <?php } ?>
                                                    </li>
                                                    <li class="match-card__score-item">
                                                        <p class="match-card__command-name <?= $match->teams_class[1] ?>"><?= $match->teams_short_name[1] ?></p>
                                                        <?php if($match->status == 'Технический результат'){ ?>
                                                            <p class="match-card__score match-score-number"><?= $match->result[1] ?></p>
                                                        <?php } ?>
                                                    </li>
                                                </ul>
                                                <p class="match-card__stadium address-stadium"><?= $match->venue ?></p>

                                                <?= $match->btn_more ?>
                                            </div>
                                        </div>

                                        <?
                                        break;
                                    default:
                                        // code...
                                        break;
                                }
                                //  dd( $event);
                            } ?>

                        </div>
                    </div>
                </div>
            <? } ?>>
        </div>
    </section>
    <section class="tilt-cards index__tilt-cards">
        <div class="container container--small tilt-cards__container">
            <ul class="tilt-cards__list">
                <?php dynamic_sidebar('main') ?>
                <!-- <li class="tilt-cards__item tilt-card-shop anim-item"><a class="tilt-card-shop__link tilt-card" href="<?= get_field('magazin_oficzialnoj_atributiki_ssylka') ?>">
                    <div class="tilt-card-shop__bg-img-wrap">
                        <div class="tilt-card-shop__bg-img" style="background-image: url('<?= get_template_directory_uri() ?>/images/content/shop-club.png');"></div>
                    </div>
                    <div class="tilt-card-shop__border" style="border-color: #ABD8FF;"></div>
                    <div class="tilt-card-shop__description">
                        <div class="tilt-card-shop__description-inner">
                            <h3 class="tilt-card-shop__title">Будь в цветах клуба</h3>
                            <p class="tilt-card-shop__text">Магазин официальной атрибутики</p>
                        </div>
                    </div></a>
                <div class="tilt-card-shop__shadow"></div>
            </li>
            <li class="tilt-cards__item tilt-card-shop anim-item"><a class="tilt-card-shop__link tilt-card" href="<?= get_field('klubnye_karty_ssylka') ?>">
                    <div class="tilt-card-shop__bg-img-wrap">
                        <div class="tilt-card-shop__bg-img" style="background-image: url('<?= get_template_directory_uri() ?>/images/content/cards-club.png');"></div>
                    </div>
                    <div class="tilt-card-shop__border" style="border-color: #0A8BFF;"></div>
                    <div class="tilt-card-shop__description">
                        <div class="tilt-card-shop__description-inner">
                            <h3 class="tilt-card-shop__title">Клубные карты</h3>
                            <p class="tilt-card-shop__text">Скидки и бонусы ждут Вас!</p>
                        </div>
                    </div></a>
                <div class="tilt-card-shop__shadow"></div>
            </li> -->
            </ul>
        </div>
    </section>

<?
$teams = 0;
$timer = [
    'days' => 0,
    'hours' => 0,
    'minutes' => 0,
    'seconds' => 0,
];
$calendar = new SP_Calendar(ID);
$calendar->orderby = 'date';
$calendar->order = 'ASC';
$calendar->team = MAIN_TEAM_ID;


$data = $calendar->data();

$usecolumns = $calendar->columns;

$eventID = 0;

foreach ($data as $key => $event) {
    $match_date = strtotime($event->post_date);
    $next_match_date = strtotime($data[$key + 1]->post_date);
    $time = floor((($next_match_date - $match_date) / 2) / 86400);
    if (floor(($match_date - $nowTimeMsk) / 86400) >= -$time ){
        $eventID = $event->ID;
        $teams = get_post_meta($event->ID, 'sp_team');
        if(!in_array(MAIN_TEAM_ID, $teams))continue;

        $venue = get_the_terms($event->ID, 'sp_venue')[0]->name;

        $term_league = get_the_terms($event->ID, 'sp_league')[0];
        $league_img_at_main = get_field('img_at_main', $term_league);
        $league = $term_league->name;
        //dd($league);
        $date_html = get_post_time('d.m', false, $event);
        $time = $event->venue;
        $game_time = get_post_meta($event->ID, 'sp_minutes', true);

        //  dd($game_time);

        $stats = GetPlayerStats($id, get_post_meta($event->ID, 'sp_players')[0]);


        //dd($event->post_date);
        $rem = strtotime($event->post_date) - $nowTimeMsk;
        $day = floor($rem / 86400);
        $hr = floor(($rem % 86400) / 3600);
        $min = floor(($rem % 3600) / 60);
        $sec = ($rem % 60);
        if ($day) $timer['days'] = $day;
        if ($hr) $timer['hours'] = $hr;
        if ($min) $timer['minutes'] = $min;
        if ($sec) $timer['seconds'] = $sec;
        break;
    }
}
if ($league_img_at_main == '') {
    $league_img_at_main = get_field('fonovoe_izobrazhenie_glavnogo_matcha');
}

?>
    <section class="main-event mask-opacity mask-opacity--top mask-opacity--top-blue"
             style="background-image: url('<?= $league_img_at_main ?>')">
        <div class="main-event__container">
            <div class="main-event__wrapper">
                <div class="main-event__match">
                    <div class="main-event__match-header">
                        <h2 class="section-title main-event__title custom-font custom-font--bold-italic">Главное</h2>
                        <div class="main-event__match-league commands-icon">
                        <img class="commands-icon__img" src="<?= get_template_directory_uri() ?>/images/icon/league/olimp.png" alt=""></div>
                        <?php if($timer['seconds'] >= 0 && $timer['minutes'] >= 0 && $timer['hours'] >= 0 && $timer['days'] >= 0): ?>
                        <ul class="main-event_when-play time-left">
                            <li class="time-left__item">
                                <p class="time-left__item-value days"><?= $timer['days'] ?></p>
                                <p class="time-left__item-key">дн</p>
                            </li>
                            <li class="time-left__item">
                                <p class="time-left__item-value hours"><?= $timer['hours'] ?></p>
                                <p class="time-left__item-key">ч</p>
                            </li>
                            <li class="time-left__item">
                                <p class="time-left__item-value minutes"><?= $timer['minutes'] ?></p>
                                <p class="time-left__item-key">мин</p>
                            </li>
                            <li class="time-left__item">
                                <p class="time-left__item-value seconds"><?= $timer['seconds'] ?></p>
                                <p class="time-left__item-key">сек</p>
                            </li>
                        </ul>
                        <?php endif; ?>
                        <p class="main-event__match-league-name"><?= $league ?></p>
                        <p class="main-event__match-league-stadium"><?= $venue ?></p>
                    </div>
                    <div class="main-event__how-play">
                        <div class="main-event__command-play">
                            <div class="main-event__command commands-icon"><?= sp_get_logo($teams[0], 'mini', ['itemprop' => 'url']) ?></div>
                            <p class="main-event__command-name"><?= sp_team_short_name($teams[0]) ?></p>
                        </div>
                        <div class="main-event__command-play">
                            <div class="main-event__command commands-icon"><?= sp_get_logo($teams[1], 'mini', ['itemprop' => 'url']) ?></div>
                            <p class="main-event__command-name"><?= sp_team_short_name($teams[1]) ?></p>
                        </div>
                    </div>

                    <? $buy_ticket = get_field('buy_ticket', $eventID); ?>

                    <?php if ($teams[0] == MAIN_TEAM_ID && $buy_ticket != ''): ?>
                        <a class="main-event__buy-ticket buy-ticket buy-ticket--category-blue buy-ticket--full"
                           href="<?= $buy_ticket ?>"><span>Купить билет</span></a>
                    <?php endif; ?>


                </div>
                <div class="main-event__news-events">
                    <div class="main-event__navigation">
                        <div class="link-border swiper-button-prev main-event__button-prev"><img
                                    class="link-border__img"
                                    src="<?= get_template_directory_uri() ?>/images/icon/arrow-prev.svg"
                                    alt="Предыдущий слайд">
                        </div>
                        <div class="link-border swiper-button-next main-event__button-next"><img
                                    class="link-border__img"
                                    src="<?= get_template_directory_uri() ?>/images/icon/arrow-next.svg"
                                    alt="Следующий слайд">
                        </div>
                    </div>
                    <div class="js-slider js-slider--main-event main-event__slider swiper-container"
                         style="opacity: 0;">
                        <div class="swiper-wrapper">
                            <?
                            $monthes = [
                                'Января',
                                'Февраля',
                                'Марта',
                                'Апреля',
                                'Мая',
                                'Июня',
                                'Июля',
                                'Августа',
                                'Сентября',
                                'Октября',
                                'Ноября',
                                'Декабря',
                            ];
                            $news = get_posts([
                                'numberposts' => -1,
                                'category' => 0,
                                'orderby' => 'date',
                                'order' => 'DESC',
                                'include' => [],
                                'exclude' => [],
                                'meta_key' => '',
                                'meta_value' => '',
                                'post_type' => ['news', 'photogallery'],
                                'suppress_filters' => false,
                                'meta_query' => [
                                    'relation' => 'AND',
                                    [
                                        'key' => 'match',
                                        'value' => $eventID,
                                        'compare' => 'LIKE',
                                    ],
                                    [
                                        'key' => 'privyazat_k_matchu',
                                        'value' => true,
                                    ],
                                ],
                            ]);

                            $polls = get_posts(
                                [
                                    'post_type' => 'poll',
                                    'posts_per_page' => -1,
                                    'orderby' => 'date',
                                    'order' => 'DESC',
                                    'post_status' => ['publish', 'future'],
                                    'meta_query' => [
                                        [
                                            'key' => 'match',
                                            'value' => $eventID,
                                            'compare' => 'LIKE',
                                        ],
                                    ],
                                ]);
                            $news_polls = [];
                            foreach($news as $item) {
                                $news_polls[$item->ID]['title'] = get_the_title($item->ID);
                                $news_polls[$item->ID]['date'] = get_the_date('F j', $item->ID);
                                $news_polls[$item->ID]['day'] = (int)date('d', strtotime($item->post_date));
                                $news_polls[$item->ID]['month'] = (int)date('m', strtotime($item->post_date));
                                $news_polls[$item->ID]['cat'] = get_the_terms($item->ID, 'news_categories')[0]->name;
                                $news_polls[$item->ID]['link'] = get_permalink($item->ID);
                                $news_polls[$item->ID]['img'] = get_field('izobrazhenie', $item->ID) ? get_field('izobrazhenie', $item->ID)['sizes']['medium'] : get_the_post_thumbnail_url($item->ID);
                            }
                            foreach($polls as $item) {
                                $news_polls[$item->ID]['title'] = get_the_title($item->ID);
                                $news_polls[$item->ID]['date'] = get_the_date('F j', $item->ID);
                                $news_polls[$item->ID]['day'] = (int)date('d', strtotime($item->post_date));
                                $news_polls[$item->ID]['month'] = (int)date('m', strtotime($item->post_date));
                                $news_polls[$item->ID]['cat'] = 'Опрос';
                                $news_polls[$item->ID]['link'] = get_permalink($item->ID);
                                $img = get_field('izobrazhenie', $poll->ID)['sizes']['medium'];
                                $img = $img ? $img : '/images/content/poll-match-center.svg';
                                $news_polls[$item->ID]['img'] = $img;
                            }
                            $news_polls = wp_list_sort($news_polls, 'month', 'DESC');
                            $news_polls = wp_list_sort($news_polls, 'day', 'DESC');
                            foreach ($news_polls as $item) {
                                ?>
                                <div class="swiper-slide news-card">
                                    <a class="news-card__link" href="<?= $item['link'] ?>"></a>
                                    <div class="news-card__img-wrap">
                                        <?php if ($item['img']): ?>
                                            <img class="news-card__img"
                                                 src="<?= $item['img']?>"
                                                 alt="<?= $item['title'] ?>">
                                        <?php endif; ?>
                                    </div>
                                    <div class="news-card__content">
                                        <div class="news-card__category-date">
                                            <a class="news-card__category" href="#"><?= $item['cat'] ?></a>
                                            <p class="news-card__date"><?= $item['date'] ?></p>
                                        </div>
                                        <p class="news-card__title"><?= $item['title'] ?></p>
                                        <a class="news-card__more more-details" href="<?= $item['link'] ?>">Подробнее</a>
                                    </div>
                                </div>
                            <? }?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php
        $banner = get_field('img', get_the_ID());
        $banner_bg_color = get_field('color', get_the_ID());
        if($banner):
    ?>
    <section class="league-banner">
        <div class="league-banner__wrap" style="background-color: <?= $banner_bg_color ?>;">
            <img src="<?= $banner['sizes']['large'] ?>" alt="Баннер" class="league-banner__image">
        </div>
    </section>
    <?php endif; ?>
    <section class="news-club index__news-club">
        <div class="container container--small news-club__container full-container">
            <div class="news-club__head">
                <h2 class="section-title news-club__title custom-font custom-font--bold-italic">Новости клуба</h2>
                <div class="js-slider js-slider--news-tags  swiper-container">
                    <div class="news-club__slider-wrapper swiper-wrapper">
                        <?
                        $categories = get_categories([
                            'taxonomy' => 'news_categories',
                            'type' => 'news',
                            'child_of' => 0,
                            'parent' => '',
                            'orderby' => 'name',
                            'order' => 'ASC',
                            'hide_empty' => 0,
                            'hierarchical' => 1,
                            'exclude' => '',
                            'include' => '',
                            'number' => 0,
                            'pad_counts' => false,
                        ]);

                        foreach ($categories as $cat) {
                            ?>
                            <div class="swiper-slide news-club__slide">
                                <button class="news-club__filter-btn filters-button <?= $cat->count > 0 ? 'news-club__filter-btn_enable' : '' ?>"
                                        type="button" <?= $cat->count <= 0 ? 'disabled' : '' ?>
                                        data-id="<?= $cat->term_id ?>"><?= $cat->name ?></button>
                            </div>
                        <? } ?>
                    </div>
                </div>
            </div>
            <ul class="news-club__list">
                <?
                $news = get_posts([
                    'numberposts' => 7,
                    'category' => 0,
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'include' => [],
                    'exclude' => [],
                    'meta_key' => '',
                    'meta_value' => '',
                    'post_type' => 'news',
                    'suppress_filters' => true, // подавление работы фильтров изменения SQL запроса
                ]);
                $i = 0;

                foreach ($news as $item) {
                    setup_postdata($item);
                    $cat = get_the_terms($item->ID, 'news_categories')[0]->name;
                    $img = get_field('izobrazhenie', $item->ID) ? get_field('izobrazhenie', $item->ID) : get_the_post_thumbnail_url($item->ID, 'medium');
                    $img = $img ? $img : get_template_directory_uri() . '/images/content/news_no_img.png';
                    $date = get_the_date('F j', $item->ID);
                    $title = get_the_title($item->ID);
                    if ($i == 0) {
                        ?>

                        <li class="news-club__item news-club__item--big news-card news-card--big"><a
                                    class="news-card__link"
                                    href="<?= the_permalink($item->ID) ?>"></a>
                            <div class="news-card__img-wrap">
                                <img class="news-card__img"
                                     src="<?= $img ? $img['sizes']['large']  : get_template_directory_uri() . '/images/content/news_no_img.png'  ?>"
                                     alt="<?= $title ?>">
                            </div>
                            <div class="news-card__content">
                                <div class="news-card__category-date">
                                    <span class="news-card__category"><?= $cat ?></span>
                                    <p class="news-card__date"><?= $date ?></p>
                                </div>
                                <p class="news-card__title"><?= $title ?></p><a
                                        class="news-card__more more-details more-details--white"
                                        href="<?= the_permalink($item->ID) ?>">Подробнее</a>
                            </div>
                        </li>
                    <? } else { ?>
                        <li class="news-club__item news-card"><a class="news-card__link"
                                                                 href="<?= the_permalink($item->ID) ?>"></a>
                            <div class="news-card__img-wrap">
                                <img class="news-card__img"
                                     src="<?= $img ? $img['sizes']['medium']  : get_template_directory_uri() . '/images/content/news_no_img.png' ?>"
                                     alt="<?= $title ?>">
                            </div>
                            <div class="news-card__content">
                                <div class="news-card__category-date"><a class="news-card__category"
                                                                         href="#"><?= $cat ?></a>
                                    <p class="news-card__date"><?= $date ?></p>
                                </div>
                                <p class="news-card__title"><?= $title ?></p><a
                                        class="news-card__more more-details" href="<?= the_permalink($item->ID) ?>">Подробнее</a>
                            </div>
                        </li>
                    <? }
                    ++$i;
                }
                wp_reset_postdata(); ?>
            </ul>
            <div class="news-club__all-news"><a class="news-club__more more-details more-details--circle"
                                                href="<?= get_permalink(66) ?>">Ко всем новостям</a></div>
        </div>
    </section>
<?php
$args = [
    'posts_per_page' => 3,
    'post_type' => 'club_tv',
];
$videos = new WP_Query($args);
if (!empty($videos->posts)):
    ?>

    <section class="club-tv">
        <div class="container container--small club-tv__container">
            <div class="club-tv__header">
                <div class="club-tv__title-wrap">
                    <p class="club-tv__category category-with-circle">Видеогалерея</p>
                    <h2 class="section-title club-tv__title custom-font custom-font--bold-italic">Видео</h2>
                </div>
                <a class="more-details more-details--white more-details--circle" href="<?= get_permalink(1837) ?>">Ко
                    всем видео</a>
            </div>
        </div>
        <div class="container container--middle">
            <div class="club-tv__videogallery videogallery">
                <?php
                $i = 0;
                foreach ($videos->posts as $video):

                    $id = $video->ID;
                    $i++;
                    $img = get_field('izobrazhenie', $id);
                    $group = get_field('group', $id);
                    $link = get_permalink($id);
                    $title = convert_quotes_to_typographic($video->post_title);

                   // dd($img['sizes']);
                    ?>
                    <?php if ($i == 1): ?>
                    <div class="videogallery__item video-card video-card--full"
                         style="background-image: url('<?= $img['sizes']['medium_large'] ?>')">
                        <a class="video-card__link" href="<?= $link ?>">
                            <svg class="video-card__icon" width="60" height="60" viewBox="0 0 60 60" fill="none"
                                 xmlns="http://www.w3.org/2000/svg">
                                <circle cx="30" cy="30" r="28.5" stroke="white" stroke-width="3"/>
                                <path d="M27 36.5V24L36.5 30L27 36.5Z" fill="white"/>
                            </svg>
                            <div class="video-card__description">
                                <p class="video-card__category video-card__category--mini">Видео</p>
                                <p class="video-card__title video-card__title--mini"><?= $title ?></p>
                            </div>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="videogallery__item video-card" style="background-image: url('<?= $img['sizes']['medium'] ?>')">
                        <a class="video-card__link" href="<?= $link ?>">
                            <svg class="video-card__icon" width="60" height="60" viewBox="0 0 60 60" fill="none"
                                 xmlns="http://www.w3.org/2000/svg">
                                <circle cx="30" cy="30" r="28.5" stroke="white" stroke-width="3"/>
                                <path d="M27 36.5V24L36.5 30L27 36.5Z" fill="white"/>
                            </svg>
                            <div class="video-card__description">
                                <p class="video-card__category video-card__category--mini">Видео</p>
                                <p class="video-card__title video-card__title--mini"><?= $title ?></p>
                            </div>
                        </a>
                    </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>
    <section class="club-photogallery">
        <div class="container container--small club-photogallery__container">
            <div class="club-photogallery__header">
                <h2 class="section-title club-photogallery__title custom-font custom-font--bold-italic">Фотоальбомы</h2>
                <a
                        class="more-details more-details--circle" href="/photogalleries">Ко всем альбомам</a>
            </div>
            <ul class="photogallery">
                <? $last_pg = get_posts([
                    'posts_per_page' => 3,
                    'orderby' => [
                        'date' => 'DESC',
                      ],
                    'post_type' => 'photogallery',
                    'suppress_filters' => true, // подавление работы фильтров изменения SQL запроса
                ]);
                // $last_pg = array_reverse($last_pg);
                foreach ($last_pg as $photo) {
                    setup_postdata($photo);
                    ?>
                    <li class="photogallery__item photo-album-card">
                        <a class="photo-album-card__link" href="<?= the_permalink($photo->ID) ?>"></a>
                        <div class="photo-album-card__wrapper">
                            <div class="photo-album-card__img-wrap">
                                <img class="photo-album-card__img" src="<?= get_field('izobrazhenie', $photo->ID)['sizes']['medium_large'] ?>" alt="<?= $photo->post_title ?>">
                            </div>
                            <div class="photo-album-card__description">
                                <header class="photo-album-card__header">
                                    <ul class="photo-album-card__category-list">
                                        <li class="photo-album-card__category-item"><a
                                                    class="photo-album-card__category-link" href="#">Фото</a></li>
                                    </ul>
                                    <h3 class="photo-album-card__title"><?= convert_quotes_to_typographic($photo->post_title) ?></h3>
                                </header>
                                <div class="photo-album-card__excerpt">
                                    <div class="photo-album-card__excerpt-text"><?= convert_quotes_to_typographic($photo->post_content) ?></div>
                                </div>
                            </div>
                        </div>
                    </li>
                <? }
                wp_reset_postdata(); ?>
            </ul>
        </div>
    </section>

<?
$args = [
    'posts_per_page' => -1,
    'post_type' => 'sp_player',
    'meta_query' => [
        [
            'key' => 'sp_current_team',
            'value' => MAIN_TEAM_ID,
        ],
    ],
    'tax_query' => [
        'relation' => 'AND',
        [
            'taxonomy' => 'sp_season',
            'field' => 'id',
            'terms' => $curr_season_id,
        ],
        [
            'taxonomy' => 'sp_league',
            'field' => 'id',
            'terms' => $curr_league_id,
        ],
    ],
    'post_status' => 'publish',
];
$list = new WP_Query($args);
$bombers = [];
$all_goals = [];
$goals = 0;
foreach ($list->posts as $item) {
    $playerObj = new SP_Player($item->ID);
    $leagues = $playerObj->leagues();
    foreach ($leagues as $league) {
        if ($league->term_id == $curr_league_id) {
            $stats = $playerObj->data($league->term_id, false, -1);
            foreach ($stats as $stat) {
                if ($stat['name'] === $curr_season) {
                    $goals += $stat['goals'];
                    $goals += $stat['penalty'];
                }
            }
            break;
        }
    }
    if ($goals == 0) {
        continue;
    }
    $bomber_goals[$item->ID]['goals'] = $goals;
    $bomber_goals[$item->ID]['player'] = $item->ID;
    $goals = 0;
}

$bomber_goals = wp_list_sort($bomber_goals, 'goals', 'desc');
$bomber_goals = array_slice($bomber_goals, 0, 8);

foreach ($bomber_goals as $value) {
    $bombers[] = get_post($value['player']);
}
// dd($bombers);
// $list = new SP_Player_List( 218 );
// $data = apply_filters('sportspress_player_list_data', $list->data( false, $leagues, $seasons, $team ) , $id );
// unset($data[0]);
?>

    <section
            class="players-slider mask-opacity mask-opacity--top mask-opacity--bottom mask-opacity--top-light-blue mask-opacity--bottom-white">
        <div class="container container--small players-slider__container">
            <div class="js-slider js-slider--players players-slider__slider swiper-container">
                <p class="players-slider__number" data-player-field="number"><span></span></p>
                <div class="players-slider__main-info players-slider__main-info--active">
                    <p class="players-slider__first-name" data-player-field="first-name"><span></span></p>
                    <p class="players-slider__last-name" data-player-field="last-name"><span></span></p>
                    <div class="players-slider__main-info-wrap">
                        <p class="players-slider__position" data-player-field="position"><span></span></p>
                        <ul class="players-slider__info-list">
                            <li class="players-slider__info-item players-slider__info-item--bday">
                                <p class="players-slider__info-value" data-player-field="bday"><span></span></p>
                                <p class="players-slider__info-key">Дата рождения</p>
                            </li>
                            <li class="players-slider__info-item players-slider__info-item--debute">
                                <p class="players-slider__info-value" data-player-field="club-debute"><span></span></p>
                                <p class="players-slider__info-key">Дебют в клубе</p>
                            </li>
                            <li class="players-slider__info-item players-slider__info-item--birthplace">
                                <p class="players-slider__info-value" data-player-field="birthplace"><span></span></p>
                                <p class="players-slider__info-key">Место рождения</p>
                            </li>
                            <li class="players-slider__info-item players-slider__info-item--lead-leg">
                                <p class="players-slider__info-value" data-player-field="lead-leg"><span></span></p>
                                <p class="players-slider__info-key">Ведущая нога</p>
                            </li>
                            <li class="players-slider__info-item players-slider__info-item--growth">
                                <p class="players-slider__info-value" data-player-field="growth"><span></span></p>
                                <p class="players-slider__info-key">Рост</p>
                            </li>
                            <li class="players-slider__info-item players-slider__info-item--weight">
                                <p class="players-slider__info-value" data-player-field="weight"><span></span></p>
                                <p class="players-slider__info-key">Вес</p>
                            </li>
                        </ul>
                        <a class="buy-ticket players-slider__profile-link" href="#"
                           data-player-field="url-profile"><span>К профилю игрока</span></a>
                    </div>
                </div>
                <div class="swiper-wrapper players-slider__wrapper">
                    <? foreach ($bombers as $item) {
                        $name = explode(' ', $item->post_title);
                        $logo = get_the_post_thumbnail_url($item->ID, 'large');
                        $playerObj = new SP_Player($item->ID);
                        $metrics = (object)$playerObj->metrics;
                        $birth = get_field('date_birth', $item->ID);
                        $foot = get_field('vedushhaya_noga', $item->ID);
                        $debut = get_field('date_debut', $item->ID);
                        $link = get_permalink($item->ID);
                        // $numbers = $sp_player->numbers();
                        // $position = sp_array_value( $item, 'position', null );
                        // if ( $position == null || ! $position ) {
                        // 	$positions = wp_strip_all_tags( get_the_term_list( $key, 'sp_position', '', ', ' ) );
                        // } else {
                        // 	$position_term = get_term_by( 'id', $position, 'sp_position', ARRAY_A );
                        // 	$positions = sp_array_value( $position_term, 'name', '&mdash;' );
                        // }

                        //dd($positions);
                        ?>
                        <div class="swiper-slide players-slider__player">
                            <div class="players-slider__player-img-wrap">
                                <img class="players-slider__player-img" src="<?= $logo ?>" width="450" height="600"
                                     alt="">
                            </div>
                            <div class="players-slider__player-info">
                                <span data-player-field-hidden="first-name"><?= $name[0] ?></span>
                                <span data-player-field-hidden="last-name"><?= $name[1] ?></span>
                                <span data-player-field-hidden="position"><?= $playerObj->positions()[0]->name ?></span>
                                <span data-player-field-hidden="number"><?= $playerObj->number ?></span>
                                <span data-player-field-hidden="bday"><?= $birth ?></span>
                                <span data-player-field-hidden="birthplace"><?= $metrics->place_birth ?></span>
                                <span data-player-field-hidden="growth"><?= $metrics->height ?></span>
                                <span data-player-field-hidden="club-debute"><?= $debut ?></span>
                                <span data-player-field-hidden="lead-leg"><?= $foot ?></span>
                                <span data-player-field-hidden="weight"><?= $metrics->weight ?></span>
                                <span data-player-field-hidden="url-profile"><?= $link ?></span></div>
                        </div>
                    <? } ?>
                </div>
                <div class="players-slider__navigation">
                    <div class="link-border swiper-button-prev players-slider__button-prev"><img
                                class="link-border__img"
                                src="<?= get_template_directory_uri() ?>/images/icon/arrow-prev.svg"
                                alt="Предыдущий слайд">
                    </div>
                    <div class="link-border swiper-button-next players-slider__button-next"><img
                                class="link-border__img"
                                src="<?= get_template_directory_uri() ?>/images/icon/arrow-next.svg"
                                alt="Следующий слайд">
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php

$curr_season_id = get_option('sportspress_season', null);
// $curr_league_id = get_option( 'sportspress_league', null );
$main_season = get_term($curr_season_id);

$junior_main_leagues = get_field('junior_league', get_the_ID());
$main_league = get_field('main_league', get_the_ID());
?>
    <section class="season-statistics index__season-statistics">
        <div class="container container--small season-statistics__container">
            <div class="season-statistics__header"
                 style="display:flex; justify-content:space-between;align-items: center;">
                <h2 class="section-title season-statistics__title custom-font custom-font--bold-italic">
                    Сезон <?= $main_season->name ?></h2>

                <div class="block-data-with-select__selects" id="selects-block" data-action="filterSeasonStatistic">
                    <p class="block-data-with-select__select-title">Основной турнир:</p>
                    <div class="block-data-with-select__select custom-select">
                        <select class="custom-select__select js-select js-select--leagues js-select--season"
                                id="league">
                            <option value="<?= $main_league->term_id ?>" selected><?= $main_league->name ?></option>
                            <?php foreach($junior_main_leagues as $j_league): ?>
                                <option value="<?= $j_league->term_id ?>"><?= $j_league->name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <ul class="season-statistics__list">
                <li class="season-statistics__item">
                    <p class="season-statistics__item-title page-aside__element-title page-aside__element-title--scroll">
                        Последние результаты</p>
                    <div class="js-slider js-slider--last-results swiper-container season-statistics__item-content season-statistics__last-results season-statistics__last-results-wrapper_flex">
                        <ul class="last-results season-statistics__last-results-wrapper swiper-wrapper "
                            id="last_result">
                            <?
                            $args = [
                                'posts_per_page' => 1,
                                'post_type' => 'sp_calendar',
                                'tax_query' => [
                                    'relation' => 'AND',
                                    [
                                        'taxonomy' => 'sp_season',
                                        'field' => 'id',
                                        'terms' => $curr_season_id,
                                    ],
                                    [
                                        'taxonomy' => 'sp_league',
                                        'field' => 'id',
                                        'terms' => $main_league->term_id,
                                    ],
                                ],
                            ];
                            $posts_calendar = new WP_Query($args);
                            $calendar = new SP_Calendar($posts_calendar->posts[0]->ID);
                            $calendar->order = 'DESC';
                            $calendar->status = 'publish';
                            $calendar->league = $main_league->term_id;
                            $calendar->season = $curr_season_id;
                            $data = $calendar->data();
                            $usecolumns = $calendar->columns;
                            $i = 0;

                            foreach ($data as $event) {
                                if ($i >= 5) break;
                                // if (get_the_terms($event->ID, 'sp_season')[0]->term_id == $curr_season_id && get_the_terms($event->ID, 'sp_league')[0]->term_id == $main_league->term_id) {
                                $teams = get_post_meta($event->ID, 'sp_team');
                                $main_results = apply_filters('sportspress_event_list_main_results', sp_get_main_results($event), $event->ID);
                                $date_html = get_post_time('d.m', false, $event);
                                $link = get_permalink($event->ID);
                                ?>
                                <li class="last-results__item last-results__item--slide swiper-slide">
                                    <a class="link_hover_border" href="<?= $link ?>">
                                        <div class="last-results__score-wrap result-score">
                                            <div class="result-score__command-wrap">
                                                <div class="result-score__command-icon commands-icon commands-icon--mini"><?= sp_get_logo($teams[0], 'mini', ['itemprop' => 'url']) ?></div>
                                                <p class="result-score__name"><?= sp_team_abbreviation($teams[0]) ?></p>
                                            </div>
                                            <div class="result-score__result-date">
                                                <div class="result-score__result">
                                                    <span><?= $main_results[0] ? $main_results[0] : 0 ?></span><span>&nbsp;:&nbsp;</span><span><?= $main_results[1] ? $main_results[1] : 0 ?></span>
                                                </div>
                                                <p class="last-results__date result-score__date"><?= $date_html ?></p>
                                            </div>
                                            <div class="result-score__command-wrap">
                                                <div class="result-score__command-icon commands-icon commands-icon--mini"><?= sp_get_logo($teams[1], 'mini', ['itemprop' => 'url']) ?></div>
                                                <p class="result-score__name"><?= sp_team_abbreviation($teams[1]) ?></p>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <? $i++;
                            }
                            // } ?>
                        </ul>
                    </div>
                    <a class="season-statistics__item-link season-statistics__item-link--last-results btn btn--light-blue"
                       href="/results?type=publish">Ко всем результатам</a>
                </li>
                <?
                $args = [
                    'posts_per_page' => -1,
                    'post_type' => 'sp_player',
                    'post_status' => ['publish'],
                    'orderby' => 'meta_value_num',
                    'order' => 'asc',
                    'meta_query' => [
                        [
                            'key' => 'sp_team',
                            'value' => MAIN_TEAM_ID,
                            'compare' => 'IN',
                        ],
                    ],
                    'tax_query' => [
                        'relation' => 'AND',
                        [
                            'taxonomy' => 'sp_season',
                            'field' => 'id',
                            'terms' => $curr_season_id,
                        ],
                        [
                            'taxonomy' => 'sp_league',
                            'field' => 'id',
                            'terms' => $main_league->term_id,
                        ],
                    ],
                ];
                $players = new WP_Query($args);
                $goals = 0;
                ?>
                <li class="season-statistics__item">
                    <p class="season-statistics__item-title page-aside__element-title">Бомбардиры</p>
                    <ul class="player-statistics season-statistics__item-content" id="bombs">
                        <?php foreach ($players->posts as $player) {

                            $player_ID = $player->ID;
                            $playerObj = new SP_Player($player_ID);
                            $nationality = $playerObj->nationalities();
                            $name = explode(' ', $player->post_title);
                            $img = get_the_post_thumbnail_url($player_ID, array(96, 128));
                            $link = get_permalink($player_ID);
                            // $times = $events->data( array('player' => $player_ID));

                            $leagues = $playerObj->leagues();
                            foreach ($leagues as $league) {
                                if ($league->term_id == $main_league->term_id) {
                                    $stats = $playerObj->data($league->term_id, false, -1);
                                    foreach ($stats as $stat) {
                                        if ($stat['name'] === $curr_season) {
                                            $goals += $stat['goals'];
                                            $goals += $stat['penalty'];
                                        }
                                    }
                                }
                            }
                            if ($goals == 0) {
                                continue;
                            }
                            $all_goals[$player->ID]['goals'] = $goals;
                            $all_goals[$player->ID]['player'] = [
                                'id' => $player_ID,
                                'full_name' => $player->post_title,
                                'name' => $name,
                                'img' => $img,
                                'link' => $link,
                            ];
                            $goals = 0;
                        }

                        $all_goals = wp_list_sort($all_goals, 'goals', 'desc');
                        $all_goals = array_slice($all_goals, 0, 5);
                        // dd($all_goals);
                        ?>
                        <?php foreach ($all_goals as $item): ?>
                            <li class="player-statistics__item">
                                <div class="player-statistics__item-wrap">
                                    <a class="player-card-position__link" href="<?= $item['player']['link'] ?>"></a>
                                    <div class="player-statistics__photo-wrap">
                                        <img class="player-statistics__photo" src="<?= $item['player']['img'] ?>"
                                             alt="<?= $item['player']['full_name'] ?>">
                                    </div>
                                    <div class="player-statistics__name-wrap">
                                        <p class="player-statistics__first-name"><?= $item['player']['name'][0] ?></p>
                                        <p class="player-statistics__last-name"><?= $item['player']['name'][1] ?></p>
                                    </div>
                                    <p class="player-statistics__amount"><?= $item['goals'] ?></p>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <a class="season-statistics__item-link btn btn--light-blue" href="/player-stats">К статистике
                        игроков</a>
                </li>

                <?
                $args = [
                    'posts_per_page' => 1,
                    'post_type' => 'sp_table',
                    'orderby' => 'meta_value_num',
                    'order' => 'DESC',
                    'tax_query' => [
                        'relation' => 'AND',
                        [
                            'taxonomy' => 'sp_season',
                            'field' => 'id',
                            'terms' => [$curr_season_id],
                        ],
                        [
                            'taxonomy' => 'sp_league',
                            'field' => 'id',
                            'terms' => $main_league->term_id,
                        ],
                    ],
                ];

                $table_posts = new WP_Query($args);


                $table_id = $table_posts->posts[0]->ID;

                $curr_league = get_term($curr_league_id);

                $table = new SP_League_Table($table_id);


                $data = $table->data();
                //dd($data);
                unset($data[0]);
                if(array_search($current_team_id, array_keys($data)) <= 4) {
                    $data = $data;
                }else if(array_search(MAIN_TEAM_ID, array_keys($data)) > 4 && array_search(MAIN_TEAM_ID, array_keys($data)) < count($data) - 5) {
                    $data = array_slice($data, array_search(MAIN_TEAM_ID, array_keys($data)) - 3, 8, true);
                } else if( array_search(MAIN_TEAM_ID, array_keys($data)) >= count($data) - 5) {
                    $data = array_slice($data, (array_search(MAIN_TEAM_ID, array_keys($data)) - 3), 8, true);
                } else {
                    $data = array_slice($data, (array_search(MAIN_TEAM_ID, array_keys($data)) - 7), 8, true);
                }
                $data = array_slice($data, 0, 8, true);
                $i = 1;
                ?>
                <li class="season-statistics__item season-statistics__item--2cell ">
                    <p class="season-statistics__item-title page-aside__element-title">Турнирная таблица</p>
                    <ul class="tournament-table season-statistics__item-content season-statistics__last-results-wrapper_flex"
                        id="table">
                        <? foreach ($data as $team_id => $team) {
                            if ($team['name'] != 'Club' && $i <= 8) {
                                $img = get_the_post_thumbnail_url($team_id, 'medium');
                                ?>
                                <li class="tournament-table__item tournament-table__item--main">
                                    <p class="tournament-table__position tournament-table__text"><?= $team['pos'] ?></p>

                                    <div class="tournament-table__command-wrap command-with-logo">
                                        <div class="tournament-table__command-icon<?= $team_id != MAIN_TEAM_ID ? '_gray' : '' ?> commands-icon commands-icon--mini command-with-logo__logo-wrap">
                                            <img class="commands-icon__img command-with-logo__log" src="<?= $img ?>"
                                                 alt="">
                                        </div>
                                        <div class="command-with-logo__text-wrap">
                                            <p class="tournament-table__command-name command-with-logo__name"><?= $team['name'] ?></p>
                                        </div>
                                    </div>

                                    <div class="tournament-table__numbers">
                                        <p class="tournament-table__wins tournament-table__text"><?= $team['p'] ?></p>
                                        <p class="tournament-table__moments tournament-table__text">
                                            <span><?= $team['f'] ?></span><span>&nbsp;-&nbsp;</span><span><?= $team['a'] ?></span>
                                        </p>
                                        <p class="tournament-table__defeat tournament-table__text"><?= round($team['pts']) ?></p>
                                    </div>
                                </li>
                            <? }
                            $i++;
                        } ?>

                    </ul>
                    <a class="season-statistics__item-link btn btn--light-blue" href="/tournament-table">К подробной
                        таблице</a>
                </li>
            </ul>
        </div>
    </section>
    <section class="press-about-club">
        <div class="container container--small press-about-club__container">
            <div class="press-about-club__header">
                <h2 class="section-title press-about-club__title custom-font custom-font--bold-italic">Пресса о
                    клубе</h2>
            </div>
            <div class="press-about-club__wrapper">
                <ul class="press-about-club__list">
                    <?
                    $press = get_posts([
                        'numberposts' => 4,
                        'orderby' => 'date',
                        'order' => 'DESC',
                        'include' => [],
                        'exclude' => [],
                        'meta_key' => '',
                        'meta_value' => '',
                        'post_type' => 'press',
                        'suppress_filters' => true, // подавление работы фильтров изменения SQL запроса
                    ]);

                    foreach ($press as $item) {
                        setup_postdata($item);

                        $text = get_field('text', $item->ID);
                        $text = convert_quotes_to_typographic($text);
                        $press_name = get_field('magazine', $item->ID);
                        ?>
                        <li class="press-about-club__item news-card news-card--transparent news-card--without-shadow"><a
                                    class="news-card__external-link" target="_blank"
                                    href="<?= get_field('ssylka_na_statyu', $item->ID) ?>"></a>
                            <div class="news-card__content">
                                <div class="news-card__category-date"><a class="news-card__category" href="#category"><?= $press_name ?></a>
                                    <p class="news-card__date"><?= date("d.m.Y", strtotime($item->post_date)) ?></p>
                                </div>
                                <p class="news-card__title"><?= convert_quotes_to_typographic($item->post_title) ?></p>
                                <p class="news-card__text"><?= $text ?></p>
                            </div>
                        </li>
                    <? }
                    wp_reset_postdata(); ?>
                </ul>

                <?
                $now = date('m');
                $monthes = [
                    (int)$now,
                    $now < 12 ? $now + 1  : '1',
                    $now < 11 ? $now + 2  : '2',
                    $now < 10 ? $now + 3  : '3',
                    $now < 9  ? $now + 4  : '4',
                    $now < 8  ? $now + 5  : '5',
                    $now < 7  ? $now + 6  : '6',
                    $now < 6  ? $now + 7  : '7',
                    $now < 5  ? $now + 8  : '8',
                    $now < 4  ? $now + 9  : '9',
                    $now < 3  ? $now + 10 : '10',
                    $now < 2  ? $now + 11 : '11',
                ];
                $count = 0;
                $human_bd = [];
                foreach ($monthes as $month) {
                    if ($month < 10) {
                        $month = '0' . $month;
                    }
                    $bd = new WP_Query([
                        'posts_per_page' => -1,
                        'post_type' => ['sp_staff', 'sp_player'],
                        'meta_key' => ['data_rozhdeniya', 'date_birth'],
                        'orderby' => 'meta_value_num',
                        'order' => 'desc',
                        'meta_query' => [
                            'relation' => 'AND',
                            [
                                'relation' => 'OR',
                                [
                                    'key' => 'data_rozhdeniya',
                                    'value' => "[0-9]{4}" . $month . "[0-9]{2}",
                                    'compare' => 'REGEXP',
                                ],
                                [
                                    'key' => 'date_birth',
                                    'value' => "[0-9]{4}" . $month . "[0-9]{2}",
                                    'compare' => 'REGEXP',
                                ],
                            ],
                            [
                                'relation' => 'OR',
                                [
                                    'key' => 'sp_current_team',
                                    'value' => [MAIN_TEAM_ID, YOUNG_TEAM_ID, YOUNG_TEAM3_ID],
                                ]
                            ],
                        ],
                    ]);
                    if (!empty($bd->posts)) {
                        foreach ($bd->posts as $post) {
                            $human_bd[] = $post;
                            $count = count($human_bd);
                        }
                        if ($count >= 5) {

                            $i = 0;
                            foreach ($human_bd as $post) {
                                if($post->post_type == 'sp_player'){
                                    $date_birth = get_field('date_birth', $post->ID);
                                    $day = (int)date('d', strtotime($date_birth));
                                    $month = (int)date('m', strtotime($date_birth));
                                } else {
                                    $date_birth = get_field('data_rozhdeniya', $post->ID);
                                    $day = (int)date('d', strtotime($date_birth));
                                    $month = (int)date('m', strtotime($date_birth));
                                }

                                if ($day < (int)date('d') && $month <= (int)date('m')) continue;
                                $i++;
                                $births[$post->ID]['month'] = $month;
                                $births[$post->ID]['day'] = $day;
                                $births[$post->ID]['player'] = $post->ID;
                            }
                            if(count($births) >= 5) {
                                $births = array_slice($births, 0, 5);
                                break;
                            }
                        }
                    }
                }
                $bd = [];
                $births = wp_list_sort($births, 'day', 'ASC');
                $births = wp_list_sort($births, 'month', 'ASC');
                // $births = array_reverse($births);
                foreach ($births as $bir) {
                    $bd[] = get_post($bir['player']);
                }
                $i = 0;
                if (!empty($bd)) { ?>
                    <div class="press-about-club__birthday birthday-players">
                        <p class="birthday-players__title">Именинники</p>
                        <ul class="birthday-players__list">

                            <? foreach ($bd as $post) {
                                $name = explode(' ', $post->post_title);
                                $date_bd = '';
                                if($post->post_type == 'sp_player'){
                                    $player = new SP_Player($post->ID);
                                    $date_bd = get_field('date_birth', $post->ID);
                                } else {
                                    $player = new SP_Staff($post->ID);
                                    $date_bd = get_field('data_rozhdeniya', $post->ID);
                                }
                                $logo = get_the_post_thumbnail_url($post->ID, 'medium');
                                $i++;
                                $date_bd = date("d.m", strtotime($date_bd));
                                $link = get_permalink($post->ID);

                                if ($i <= 1) {
                                    ?>
                                    <li class="birthday-players__item birthday-players__item--full">
                                        <div class="birthday-players__item-content">
                                            <a class="player-card-position__link" href="<?= $link ?>"></a>
                                            <div class="birthday-players__img-wrap">
                                                <img class="birthday-players__img" src="<?= $logo ? $logo : get_field('no_img_pers', 86)['sizes']['medium'] ?>" alt="">
                                            </div>
                                            <div class="birthday-players__who-wrap">
                                                <div class="birthday-players__who">
                                                    <p class="birthday-players__first-name"><?= $name[0] ?></p>
                                                    <p class="birthday-players__last-name"><?= $name[count($name) - 1] ?></p>
                                                    <p class="birthday-players__position"><?= $post->post_type == 'sp_player' ? $player->positions()[0]->name : $player->roles()[0]->name ?></p>
                                                </div>
                                                <p class="birthday-players__day"><?= $date_bd ?></p>
                                            </div>
                                        </div>
                                    </li>
                                <? } else { ?>
                                    <li class="birthday-players__item">
                                        <div class="birthday-players__item-content">
                                            <a class="player-card-position__link" href="<?= $link ?>"></a>
                                            <div class="birthday-players__img-wrap">
                                                <img class="birthday-players__img" src="<?= $logo ? $logo : get_field('no_img_pers', 86)['sizes']['medium'] ?>" alt="">
                                            </div>
                                            <div class="birthday-players__who-wrap">
                                                <div class="birthday-players__who">
                                                    <p class="birthday-players__first-name"><?= $name[0] ?></p>
                                                    <p class="birthday-players__last-name"><?= $name[count($name) - 1] ?></p>
                                                    <p class="birthday-players__position"><?= $post->post_type == 'sp_player' ? $player->positions()[0]->name : get_the_terms($player->ID, 'sp_role')[0]->name ?></p>
                                                </div>
                                                <p class="birthday-players__day"><?= $date_bd ?></p>
                                            </div>
                                        </div>
                                    </li>
                                <? }
                            }
                            wp_reset_postdata(); ?>

                        </ul>
                    </div>
                <? } ?>
            </div>
        </div>
    </section>
    <section class="mailing">
        <div class="container container--small mailing__container">
            <div class="mailing__wrapper">
                <p class="mailing__title">Хотите первыми узнавать новости команды?</p>
                <p class="mailing__subtitle">Вводя свой e-mail и нажимая кнопку «Подписаться», я даю согласие на
                    получение
                    рекламно-информационных рассылок</p>
                <form class="mailing__form" action="<?= admin_url('admin-post.php'); ?>" data-action="mailing"
                      method="post">
                    <input type="hidden" name="action" value="subscribe"/>
                    <div class="input-group input-group--middle-blue mailing__form-input-group">
                        <input class="input-group__input" type="email" name="mailing-email"
                               placeholder="Введите ваш e-mail">
                    </div>
                    <button class="mailing__button-submit btn btn--light-blue" type="submit">Подписаться</button>
                </form>
            </div>
            <img class="mailing__image" src="<?= get_template_directory_uri() ?>/images/content/mailing2.webp" alt="">
        </div>
    </section>
    <section class="social-block">
        <div class="container container--small social-block__container">
            <h2 class="section-title social-block__title custom-font custom-font--bold-italic">@odfcru</h2>
            <?php
            $insta = get_field('instagram', ID);
            $insta_img = get_field('inst_img', ID)['sizes']['large'];
            ?>
            <div class="social-block__wrapper">
                <div class="social-block__instagram">
                    <!-- <img class="social-block__instagram-img" src="<?= $insta_img ?>" alt=""> -->
                    <script src="https://apps.elfsight.com/p/platform.js" defer></script>
                    <div class="elfsight-app-06c7ae28-73a8-426b-8829-c4575942e44a"></div>
                    <a class="social-block__instagram-link btn btn--light-blue" href="<?= $insta ?>">Перейти к профилю в
                        Instagram</a></div>
                <div class="social-block__other social-aside">
                    <p class="social-aside__title">Следите за новостями в соц.сетях</p>
                    <?php
                    wp_nav_menu([
                        'menu' => 'main_social',
                        'container' => false,
                        'items_wrap' => '<ul id="%1$s" class="%2$s social-aside__list">%3$s</ul>',
                        'walker' => new Aside_Social_Menu_Walker(),
                    ])
                    ?>
                </div>
            </div>
        </div>
    </section>
    <section class="poll-block">
        <div class="container container--small poll-block__container">
        <?php
$polls_future = get_posts([
    'posts_per_page' => 4,
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
    'meta_key' => 'date',
    'meta_query' => [
        [
            'key' => 'date',
            'value' => date('Ymd', $nowTimeMsk),
            'compare' => '>',
        ],
        [
            'key' => 'season',
            'value' => $curr_season_id,
        ],
    ],
    'post_status' => 'publish',
    'post_type' => 'poll',
    'suppress_filters' => false, // подавление работы фильтров изменения SQL запроса
]);
$polls = get_posts([
    'posts_per_page' => 2,
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
    'meta_key' => 'date',
    'meta_query' => [
        [
            'key' => 'date',
            'value' => date('Ymd', $nowTimeMsk),
            'compare' => '<=',
        ],
        [
            'key' => 'season',
            'value' => $curr_season_id,
        ],
    ],
    'post_status' => 'publish',
    'post_type' => 'poll',
    'suppress_filters' => false, // подавление работы фильтров изменения SQL запроса
]);
if(count($polls) + count($polls_future) > 0):
?>
            <h2 class="section-title poll-block__title custom-font custom-font--bold-italic">Опросы</h2>
            <div class="poll-block__list-wrap">
                <ul class="poll-block__list">
                    <?php
                    $arr = [
                        'января',
                        'февраля',
                        'марта',
                        'апреля',
                        'мая',
                        'июня',
                        'июля',
                        'августа',
                        'сентября',
                        'октября',
                        'ноября',
                        'декабря',
                    ];
                    foreach ($polls as $poll):
                        $date = get_field('date', $poll->ID);
                        $month = date('m', strtotime($date)) - 1;
                        $date = date('d', strtotime($date)) . ' ' . $arr[$month];
                        //sorting poll's array on polls
                        if (get_field('tip_oprosa', $poll->ID) == 'Лучший игрок') {
                            $players = get_field('igroki', $poll->ID);
                            $sort_by_polls = [];
                            foreach ($players as $item) {
                                $sort_by_polls[] = $item['kolichestvo_golosov'];
                            }
                            array_multisort($sort_by_polls, SORT_DESC, SORT_NATURAL | SORT_FLAG_CASE, $players);

                            //get poll's winner
                            $winner = $players[0];
                            $winnerName = explode(' ', get_post($winner['igrok'])->post_title);
                            $player = new SP_Player($winner['igrok']);
                            $logo = get_the_post_thumbnail_url($winner['igrok'], 'full');
                            $pos = get_the_terms($winner['igrok'], 'sp_position');
                            $parent = get_ancestors($player->positions()[0]->term_id, 'sp_position');
                            $position = $parent ? get_term($parent[0])->name : $pos[0]->name;
                            $curr_team = in_array(MAIN_TEAM_ID, $player->current_teams()) ? MAIN_TEAM_ID : in_array(YOUNG_TEAM_ID, $player->current_teams()) ? YOUNG_TEAM_ID : YOUNG_TEAM3_ID;
                        } else {
                            $goals = get_field('goly', $poll->ID);
                            $sort_by_polls = [];
                            foreach ($goals as $item) {
                                $sort_by_polls[] = $item['kolichestvo_golosov'];
                            }
                            array_multisort($sort_by_polls, SORT_DESC, SORT_NATURAL | SORT_FLAG_CASE, $goals);


                            //get poll's winner
                            $winner = $goals[0];
                            $player = new SP_Player($winner['gol_ot_igroka']);

                            $winnerName = explode(' ', $player->post->post_title);

                            $logo = get_the_post_thumbnail_url($player->ID, 'full');
                            $pos = get_the_terms($winner['igrok'], 'sp_position');
                            $parent = get_ancestors($player->positions()[0]->term_id, 'sp_position');
                            $position = $parent ? get_term($parent[0])->name : $pos[0]->name;
                            $curr_team = in_array(MAIN_TEAM_ID, $player->current_teams()) ? MAIN_TEAM_ID : in_array(YOUNG_TEAM_ID, $player->current_teams()) ? YOUNG_TEAM_ID : YOUNG_TEAM3_ID;
                        }
                        ?>
                        <li class="poll-block__item poll-block__item--2cell news-card news-card--result-poll">
                            <a class="news-card__link" href="<?= get_the_permalink($poll->ID) ?>"></a>
                            <div class="news-card__content">
                                <div class="news-card__result-wrap">
                                    <div class="news-card__category-date"><a class="news-card__category" href="#">Результаты
                                            опроса</a>
                                        <p class="news-card__date"><?= $date ?></p>
                                    </div>
                                    <p class="news-card__title"><?= convert_quotes_to_typographic($poll->post_title) ?></p><img
                                            class="news-card__logo-command"
                                            src="<?= get_the_post_thumbnail_url($curr_team, 'full') ?>"
                                            width="80" alt="">
                                    <div class="news-card__player-info">
                                        <p class="news-card__first-name"><?= $winnerName[0] ?></p>
                                        <p class="news-card__last-name"><?= $winnerName[1] ?></p>
                                        <p class="news-card__position"><?= $position ?></p>
                                    </div>
                                </div>
                                <div class="news-card__player-img-wrap"><img class="news-card__player-img"  src="<?= $logo ?>" width="192" alt="">
                                    <p class="news-card__player-number"><?= $player->number ?></p>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    <? foreach ($polls_future as $poll) {
                        // Поскольку от 1 до 12, а в массиве, как мы знаем, отсчет идет от нуля (0 до 11),
                        // то вычитаем 1 чтоб правильно выбрать уже из нашего массива.
                        $date = get_field('date', $poll->ID);
                        $month = date('m', strtotime($date)) - 1;
                        $date = date('d', strtotime($date)) . ' ' . $arr[$month];
                        $title = convert_quotes_to_typographic($poll->post_title);
                        ?>
                        <li class="poll-block__item news-card"><a class="news-card__link" href="<?= get_the_permalink($poll->ID) ?>"></a>
                            <!-- <div class="news-card__img-wrap poll-block__item_with-boll"></div> -->
                            <div class="news-card__img-wrap"><img class="news-card__img" src="/images/content/poll-match-center-white.svg" alt="<?= $title ?>" ></div>
                            <div class="news-card__content">
                                <div class="news-card__category-date"><a class="news-card__category" href="#">Опрос</a>
                                    <p class="news-card__date"><?= $date ?></p>
                                </div>
                                <p class="news-card__title"><?= $title ?></p><a
                                        class="news-card__more more-details" href="<?= get_the_permalink($poll->ID) ?>">Подробнее</a>
                            </div>
                        </li>

                        <?php
                    } ?>
                </ul>
                <div class="poll-block__list-more"><a class="more-details more-details--circle"
                                                      href="<?= get_permalink(438) ?>">Ко всем
                        опросам</a></div>
            </div>
            <?php endif; ?>
            <!-- <div class="poll-block__mobile-app">
                <div class="mobile-app"><img class="mobile-app__iphone"
                                             src="<?= get_template_directory_uri() ?>/images/content/iphone.webp" alt="">
                    <div class="mobile-app__description">
                        <p class="mobile-app__text">Скачайте прямо сейчас</p>
                        <p class="mobile-app__title custom-font custom-font--bold-italic">Официальное мобильное
                            приложение
                            клуба</p>
                        <div class="mobile-app__download-links"><a class="mobile-app__download-link"
                                                                   href="<?= get_field('ssylki_na_mobilnoe_prilozhenie')['google_play'] ?>"><img
                                        src="<?= get_template_directory_uri() ?>/images/content/google-play.webp" alt=""></a><a
                                    class="mobile-app__download-link"
                                    href="<?= get_field('ssylki_na_mobilnoe_prilozhenie')['app_store'] ?>"><img
                                        src="<?= get_template_directory_uri() ?>/images/content/app-store.webp"
                                        alt=""></a>
                        </div>
                    </div>
                </div>
            </div> -->
        </div>
    </section>
<?php get_footer() ?>
