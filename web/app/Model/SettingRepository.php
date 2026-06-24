<?php declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;


/**
 * Key/value store for admin-tweakable options. Values are stored as strings;
 * typed accessors wrap the individual options.
 */
final readonly class SettingRepository
{
    private const string GameActive = 'game_active';


    public function __construct(
        private Explorer $explorer,
    ) {
    }


    public function get(string $name, ?string $default = null): ?string
    {
        return $this->explorer->table('setting')->get($name)?->value ?? $default;
    }


    public function set(string $name, string $value): void
    {
        $row = $this->explorer->table('setting')->get($name);
        if ($row !== null) {
            $row->update(['value' => $value]);
        } else {
            $this->explorer->table('setting')->insert(['name' => $name, 'value' => $value]);
        }
    }


    /** When on, the homepage redirects to the results board instead of the intro. */
    public function isGameActive(): bool
    {
        return $this->get(self::GameActive, '0') === '1';
    }


    public function setGameActive(bool $active): void
    {
        $this->set(self::GameActive, $active ? '1' : '0');
    }
}
