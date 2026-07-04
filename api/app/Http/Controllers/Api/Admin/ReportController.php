<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\OrderItem;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function exportParticipants(Event $event): StreamedResponse
    {
        $items = OrderItem::query()
            ->whereHas('order', fn ($q) => $q->where('event_id', $event->id)->where('status', 'paid'))
            ->with('category')
            ->get()
            ->sortBy(fn ($item) => mb_strtolower($item->participant_data['name'] ?? ''))
            ->values();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['NUMERO', 'NOME COMPLETO', 'SEXO', 'NASC', 'EQUIPE', 'MOD', 'CAT I', 'CAT II', 'CIDADE', 'CAMISA'];
        $sheet->fromArray([$headers], null, 'A1');

        $pad = max(3, strlen((string) $items->count()));

        foreach ($items as $index => $item) {
            $participant = $item->participant_data ?? [];
            $category = $item->category;
            $rowNum = $index + 2;

            $sheet->setCellValue("A{$rowNum}", str_pad($index + 1, $pad, '0', STR_PAD_LEFT));
            $sheet->setCellValue("B{$rowNum}", $participant['name'] ?? null);
            $sheet->setCellValue("C{$rowNum}", $this->resolveGender($participant['gender'] ?? null));
            $sheet->setCellValue("E{$rowNum}", $participant['team'] ?? null);
            $sheet->setCellValue("F{$rowNum}", $category ? $this->formatDistance($category->distance) : null);
            $sheet->setCellValue("G{$rowNum}", $category?->name ?? null);
            $sheet->setCellValue("H{$rowNum}", $participant['cat_ii'] ?? null);
            $sheet->setCellValue("I{$rowNum}", $participant['city'] ?? null);
            $sheet->setCellValue("J{$rowNum}", $participant['shirt_size'] ?? null);

            if (! empty($participant['birthdate'])) {
                $excelDate = ExcelDate::PHPToExcel(new \DateTime($participant['birthdate']));
                $sheet->setCellValue("D{$rowNum}", $excelDate);
                $sheet->getStyle("D{$rowNum}")
                    ->getNumberFormat()
                    ->setFormatCode('yyyy-mm-dd');
            }
        }

        $filename = Str::upper(Str::slug($event->title, '_')).'.xlsx';

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    private function resolveGender(?string $gender): ?string
    {
        return match ($gender) {
            'M', 'F' => $gender,
            default => null,
        };
    }

    private function formatDistance(string|float|null $distance): ?string
    {
        if ($distance === null) {
            return null;
        }

        $num = (float) $distance;

        return ($num == (int) $num ? (int) $num : $num).'K';
    }
}
