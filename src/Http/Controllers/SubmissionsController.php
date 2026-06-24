<?php

namespace App\FormsPlus\Http\Controllers;

use Statamic\Facades\Form;
use Statamic\Http\Controllers\CP\CpController;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionsController extends CpController
{
    public function index(string $handle)
    {
        $form = Form::find($handle);

        if (! $form) {
            return response()->json(['error' => 'Form not found.'], 404);
        }

        $columns = $this->getColumns($form);

        $submissions = $form->querySubmissions()
            ->orderBy('date', 'desc')
            ->get()
            ->map(fn ($s) => [
                'id'   => $s->id(),
                'date' => $s->date()->format('d M Y, H:i'),
                'data' => collect($s->data()->all())
                    ->map(fn ($v) => is_array($v) ? implode(', ', $v) : (string) $v)
                    ->all(),
            ])
            ->values();

        return response()->json(compact('submissions', 'columns'));
    }

    public function destroy(string $handle, string $id)
    {
        $form = Form::find($handle);

        if (! $form) {
            return response()->json(['error' => 'Form not found.'], 404);
        }

        $submission = $form->querySubmissions()->get()->first(fn ($s) => $s->id() === $id);

        if (! $submission) {
            return response()->json(['error' => 'Submission not found.'], 404);
        }

        $submission->delete();

        return response()->json(['success' => true]);
    }

    public function export(string $handle): StreamedResponse
    {
        $form = Form::find($handle);

        if (! $form) {
            abort(404);
        }

        $columns  = $this->getColumns($form);
        $submissions = $form->querySubmissions()->orderBy('date', 'desc')->get();

        return response()->streamDownload(function () use ($submissions, $columns) {
            $out = fopen('php://output', 'w');

            fputcsv($out, array_merge(['Date'], $columns));

            foreach ($submissions as $s) {
                $data = $s->data()->all();
                $row  = [$s->date()->format('Y-m-d H:i')];

                foreach ($columns as $col) {
                    $val = $data[$col] ?? '';
                    $row[] = is_array($val) ? implode(', ', $val) : (string) $val;
                }

                fputcsv($out, $row);
            }

            fclose($out);
        }, "{$handle}-submissions-".now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function getColumns($form): array
    {
        $contents = $form->blueprint()->contents();
        $columns  = [];

        foreach ($contents['tabs'] ?? [] as $tab) {
            foreach ($tab['sections'] ?? [] as $section) {
                foreach ($section['fields'] ?? [] as $fieldData) {
                    if (isset($fieldData['handle'])) {
                        $columns[] = $fieldData['handle'];
                    }
                }
            }
        }

        return $columns;
    }
}
