<?php declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;
use Nette\Database\Table\Selection;


/**
 * Append-only call log for the IVR endpoints (MyISAM table `ivr_log`). Written by the public
 * endpoint on every hit (including unknown codes, with `endpoint_id` NULL) and read in the
 * admin. All request GET params are stored as a JSON string in `params`.
 */
final readonly class IvrLogRepository
{
    public function __construct(
        private Explorer $explorer,
    ) {
    }


    public function insert(array $data): void
    {
        $this->explorer->table('ivr_log')->insert($data);
    }


    /** All log rows, newest first – for the global admin log view. */
    public function findAll(): Selection
    {
        return $this->explorer->table('ivr_log')->order('id DESC');
    }


    /** Log rows of a single endpoint, newest first. */
    public function findByEndpoint(int $endpointId): Selection
    {
        return $this->explorer->table('ivr_log')
            ->where('endpoint_id', $endpointId)
            ->order('id DESC');
    }


    /** Removes an endpoint's log rows (called when the endpoint is deleted). */
    public function deleteByEndpoint(int $endpointId): void
    {
        $this->explorer->table('ivr_log')->where('endpoint_id', $endpointId)->delete();
    }
}
