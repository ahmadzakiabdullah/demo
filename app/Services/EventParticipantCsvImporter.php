<?php

namespace App\Services;

use App\Enums\EventParticipantStatus;
use App\Enums\EventParticipantType;
use App\Models\Event;
use App\Models\EventParticipant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EventParticipantCsvImporter
{
    /**
     * @return array{created: int, participants: list<array<string, mixed>>}
     */
    public function import(Event $event, UploadedFile $file): array
    {
        $rows = $this->parseCsv($file);
        $validatedRows = $this->validateRows($event, $rows);

        $participants = DB::transaction(function () use ($event, $validatedRows) {
            $created = [];

            foreach ($validatedRows as $row) {
                $participant = EventParticipant::create([
                    'organization_id' => $event->organization_id,
                    'event_id' => $event->id,
                    'type' => $row['type'],
                    'name' => $row['name'],
                    'code' => $row['code'] ?? null,
                    'branch_id' => $row['branch_id'] ?? null,
                    'status' => $row['status'],
                    'metadata' => $row['metadata'] ?? null,
                ]);

                $created[] = [
                    'id' => $participant->id,
                    'name' => $participant->name,
                    'code' => $participant->code,
                    'type' => $participant->type->value,
                    'status' => $participant->status->value,
                ];
            }

            return $created;
        });

        return [
            'created' => count($participants),
            'participants' => $participants,
        ];
    }

    /**
     * @return list<array<string, string|null>>
     */
    private function parseCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => ['Unable to read the uploaded CSV file.'],
            ]);
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);

            throw ValidationException::withMessages([
                'file' => ['The CSV file is empty.'],
            ]);
        }

        $header = array_map(fn (string $column) => strtolower(trim($column)), $header);

        if (! in_array('name', $header, true) || ! in_array('type', $header, true)) {
            fclose($handle);

            throw ValidationException::withMessages([
                'file' => ['CSV must include at least "type" and "name" columns.'],
            ]);
        }

        $rows = [];
        $lineNumber = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $lineNumber++;

            if ($this->isEmptyRow($data)) {
                continue;
            }

            $row = [];
            foreach ($header as $index => $column) {
                $row[$column] = isset($data[$index]) ? trim((string) $data[$index]) : null;
            }

            $row['_line'] = (string) $lineNumber;
            $rows[] = $row;
        }

        fclose($handle);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => ['The CSV file contains no data rows.'],
            ]);
        }

        return $rows;
    }

    /**
     * @param  list<array<string, string|null>>  $rows
     * @return list<array<string, mixed>>
     */
    private function validateRows(Event $event, array $rows): array
    {
        $validated = [];
        $errors = [];
        $codesInFile = [];

        foreach ($rows as $row) {
            $line = $row['_line'];
            unset($row['_line']);

            $row['status'] = $row['status'] ?? EventParticipantStatus::Active->value;
            $row['branch_id'] = filled($row['branch_id'] ?? null) ? (int) $row['branch_id'] : null;
            $row['code'] = filled($row['code'] ?? null) ? $row['code'] : null;

            if ($row['code'] !== null && isset($codesInFile[$row['code']])) {
                $errors["rows.{$line}.code"][] = 'Duplicate code in CSV file.';

                continue;
            }

            if ($row['code'] !== null) {
                $codesInFile[$row['code']] = true;
            }

            $validator = Validator::make($row, [
                'type' => ['required', Rule::enum(EventParticipantType::class)],
                'name' => ['required', 'string', 'max:255'],
                'code' => [
                    'nullable',
                    'string',
                    'max:20',
                    'alpha_dash',
                    Rule::unique('event_participants', 'code')->where('event_id', $event->id),
                ],
                'branch_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('branches', 'id')->where('organization_id', $event->organization_id),
                ],
                'status' => ['required', Rule::enum(EventParticipantStatus::class)],
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $field => $messages) {
                    foreach ($messages as $message) {
                        $errors["rows.{$line}.{$field}"][] = $message;
                    }
                }

                continue;
            }

            $validated[] = $validator->validated();
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $validated;
    }

    /**
     * @param  list<string|null>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (filled($value)) {
                return false;
            }
        }

        return true;
    }
}