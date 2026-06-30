<?php declare(strict_types=1);

namespace App\Presentation\Admin\Morse;

use App\Model\MorsePresetRepository;
use App\Presentation\Admin\BasePresenter;
use Nette\Utils\Json;
use Nette\Utils\JsonException;


/**
 * Morse code generator ("Morse") – a fully client-side tool that turns text into a
 * WAV recording (Web Audio API + OfflineAudioContext, 8 kHz/16-bit/mono for the
 * phone line). PHP only renders the page and exposes a tiny JSON API to persist
 * named presets. Presets are stored as an opaque JSON blob: the server never looks
 * inside it. The built-in "Default" preset lives in the JavaScript, not the DB.
 */
final class MorsePresenter extends BasePresenter
{
    public function __construct(
        private readonly MorsePresetRepository $presets,
    ) {
        parent::__construct();
    }


    public function renderDefault(): void
    {
        // The template wires the JS up via data-url-* attributes; nothing else here.
    }


    /** JSON: list of saved presets ({id, name}), for the picker. */
    public function actionPresets(): void
    {
        $list = [];
        foreach ($this->presets->findAll() as $row) {
            $list[] = ['id' => (int) $row->id, 'name' => $row->name];
        }

        $this->sendJson($list);
    }


    /** JSON: one preset with its decoded data blob. */
    public function actionPreset(?int $id = null): void
    {
        $row = $id !== null ? $this->presets->find($id) : null;
        if ($row === null) {
            $this->error('Preset nenalezen.');
        }

        /** @var string $raw */
        $raw = $row->data;
        $this->sendJson([
            'id' => (int) $row->id,
            'name' => $row->name,
            'data' => Json::decode($raw),
        ]);
    }


    /** JSON: overwrite an existing preset's data blob (POST body: {data}). */
    public function actionSavePreset(?int $id = null): void
    {
        if (!$this->getHttpRequest()->isMethod('POST')) {
            $this->error('Použij POST.', 405);
        }

        $row = $id !== null ? $this->presets->find($id) : null;
        if ($row === null) {
            $this->error('Preset nenalezen.');
        }

        $body = $this->readJsonBody();
        $this->presets->update((int) $row->id, [
            'data' => Json::encode($body->data ?? new \stdClass),
        ]);

        $this->sendJson(['id' => (int) $row->id]);
    }


    /** JSON: create a new preset (POST body: {name, data}); returns {id, name}. */
    public function actionCreatePreset(): void
    {
        if (!$this->getHttpRequest()->isMethod('POST')) {
            $this->error('Použij POST.', 405);
        }

        $body = $this->readJsonBody();
        $name = trim((string) ($body->name ?? ''));
        if ($name === '') {
            $this->error('Zadej název presetu.', 422);
        }

        $row = $this->presets->insert([
            'name' => $name,
            'data' => Json::encode($body->data ?? new \stdClass),
        ]);

        $this->sendJson(['id' => (int) $row->id, 'name' => $row->name]);
    }


    /** Decodes the JSON request body or fails with a 400. */
    private function readJsonBody(): \stdClass
    {
        try {
            $body = Json::decode((string) $this->getHttpRequest()->getRawBody());
        } catch (JsonException) {
            $this->error('Neplatná data.', 400);
        }

        if (!$body instanceof \stdClass) {
            $this->error('Neplatná data.', 400);
        }

        return $body;
    }
}
