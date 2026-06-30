<?php declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;


/**
 * Named presets for the Morse code generator (Admin\Morse). The `data` column holds
 * an opaque JSON blob produced by the client; this repository never inspects it –
 * the presenter passes it straight through. The built-in "Default" preset lives in
 * the JavaScript and is not stored here.
 */
final readonly class MorsePresetRepository
{
    public function __construct(
        private Explorer $explorer,
    ) {
    }


    /** All presets, ordered by name – for the picker. */
    public function findAll(): Selection
    {
        return $this->explorer->table('morse_preset')->order('name');
    }


    public function find(int $id): ?ActiveRow
    {
        return $this->explorer->table('morse_preset')->get($id) ?: null;
    }


    /** Inserts a row and returns it (the caller needs the new id). */
    public function insert(array $data): ActiveRow
    {
        return $this->explorer->table('morse_preset')->insert($data);
    }


    public function update(int $id, array $data): void
    {
        $this->explorer->table('morse_preset')->where('id', $id)->update($data);
    }


    public function delete(int $id): void
    {
        $this->explorer->table('morse_preset')->where('id', $id)->delete();
    }
}
