<?php

namespace App\Imports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

class UsersImport implements ToModel, WithHeadingRow, SkipsOnError, SkipsOnFailure
{
    public array $errors   = [];
    public array $failures = [];
    public int   $imported = 0;
    public int   $skipped  = 0;

    public function model(array $row)
    {
        $row   = $this->normalize($row);
        $email = strtolower(trim((string)($row['email'] ?? '')));

        if (empty($email)) { $this->skipped++; return null; }

        if (DB::table('users')->where('email', $email)->exists()) {
            $this->skipped++;
            return null;
        }

        DB::table('users')->insertOrIgnore([
            'id'             => isset($row['id']) && $row['id'] !== '' ? (int)$row['id'] : null,
            'username'       => trim((string)($row['username'] ?? $email)),
            'password'       => (string)($row['password'] ?? ''),
            'email'          => $email,
            'role'           => strtolower(trim((string)($row['role']   ?? 'student'))),
            'status'         => strtolower(trim((string)($row['status'] ?? 'active'))),
            'remember_token' => $row['remember_token'] ?? null,
            'created_at'     => $row['created_at'] ?? now(),
            'updated_at'     => $row['updated_at'] ?? now(),
        ]);

        $this->imported++;
        return null;
    }

    private function normalize(array $row): array
    {
        return array_combine(
            array_map(fn($k) => strtolower(trim((string)$k)), array_keys($row)),
            array_values($row)
        );
    }

    public function onError(Throwable $e): void
    {
        $this->errors[] = $e->getMessage();
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $f) {
            $this->failures[] = "Row {$f->row()}: " . implode(', ', $f->errors());
        }
    }
}
