<?php declare(strict_types=1);

namespace App\Model;


/**
 * The two fixed teams. The string value is the immutable internal team code
 * (also the `team.code` PK and the prefix of the result columns in `game`);
 * the display name lives in the DB and is editable.
 */
enum TeamCode: string
{
    case Bear = 'bear';
    case Hornet = 'hornet';


    /** Crest image basename under /img/ (…-silhouette.svg, .avif, .webp). */
    public function crest(): string
    {
        return match ($this) {
            self::Bear => 'survival-lodin-crest-medved',
            self::Hornet => 'survival-lodin-crest-srsen',
        };
    }


    /** Points column for this team in the `game` table. */
    public function pointsColumn(): string
    {
        return match ($this) {
            self::Bear => 'bear_points',
            self::Hornet => 'hornet_points',
        };
    }
}
