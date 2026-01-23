<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Storage;

class JsonItemRepository
{
    protected $file = 'items.json';
    protected $summaryFile = 'summary.json';

    public function all()
    {
        if (!Storage::exists($this->file)) {
            return collect([]);
        }
        $json = Storage::get($this->file);
        return collect(json_decode($json, true));
    }

    public function getSummary()
    {
        if (!Storage::exists($this->summaryFile)) {
            return null;
        }
        return json_decode(Storage::get($this->summaryFile), true);
    }

    public function saveAll($items)
    {
        Storage::put($this->file, json_encode($items, JSON_PRETTY_PRINT));
    }

    public function saveSummary($summary)
    {
        Storage::put($this->summaryFile, json_encode($summary, JSON_PRETTY_PRINT));
    }

    public function filter($criteria = [])
    {
        $items = $this->all();
        
        return $items->filter(function ($item) use ($criteria) {
             foreach ($criteria as $key => $value) {
                 if (!isset($item[$key])) return false;
                 
                 // Simple equality check, can extend logic
                 if (is_array($value)) {
                     if (!in_array($item[$key], $value)) return false;
                 } else {
                     if ($item[$key] != $value) return false;
                 }
             }
             return true;
        });
    }

    // Metadata (last import timestamp)
    protected $metaFile = 'metadata.json';

    public function getMetadata()
    {
        if (!Storage::exists($this->metaFile)) {
            return ['imported_at' => null];
        }
        return json_decode(Storage::get($this->metaFile), true);
    }

    public function saveMetadata($data)
    {
        Storage::put($this->metaFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    // History for trends
    protected $historyFile = 'history.json';

    public function getHistory()
    {
        if (!Storage::exists($this->historyFile)) {
            return [];
        }
        return json_decode(Storage::get($this->historyFile), true);
    }

    public function addToHistory($snapshot)
    {
        $history = $this->getHistory();
        $history[] = $snapshot;
        // Keep only last 30 entries
        if (count($history) > 30) {
            $history = array_slice($history, -30);
        }
        Storage::put($this->historyFile, json_encode($history, JSON_PRETTY_PRINT));
    }
}
