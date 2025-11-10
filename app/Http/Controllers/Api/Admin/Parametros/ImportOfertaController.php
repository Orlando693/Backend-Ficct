<?php

namespace App\Http\Controllers\Api\Admin\Parametros;

use App\Http\Controllers\Controller;
use App\Support\Bitacora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportOfertaController extends Controller
{
    private const REQUIRED = ['carrera_sigla','materia_codigo','paralelo','turno','capacidad'];

    public function preview(Request $req)
    {
        $req->validate([
          'file' => ['required','file'],
          'gestion_id' => ['required','integer','min:1'],
        ]);
        $file = $req->file('file');
        $ext = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, ['csv','xlsx'])) {
            return response()->json(['message'=>'Solo CSV/XLSX.'], 422);
        }

        if ($ext === 'csv') {
            [$rows,$summary] = $this->previewCsv($file->getPathname());
        } else {
            // XLSX: vista previa simple leyendo como CSV interno (si tienes Spout/PhpSpreadsheet, cámbialo)
            return response()->json(['message'=>'Vista previa XLSX no habilitada aún. Usa CSV.'], 422);
        }

        return response()->json(['data' => ['rows'=>$rows,'totals'=>$summary]]);
    }

    public function confirm(Request $req)
    {
        $req->validate([
          'file' => ['required','file'],
          'gestion_id' => ['required','integer','min:1'],
        ]);
        $file = $req->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        if ($ext !== 'csv') return response()->json(['message'=>'Por ahora solo CSV para confirmar.'], 422);

        [$rows,$summary] = $this->previewCsv($file->getPathname());

        if ($summary['error'] > 0) {
            return response()->json(['message'=>'Hay filas con error; corrige antes de importar.'], 422);
        }

        $inserted=0; $updated=0; $skipped=0; $errors=0;

        DB::beginTransaction();
        try {
            foreach ($rows as $r) {
                $data = $r['data']; // array con claves REQUIRED
                // Resuelve materia por código
                $materia = DB::selectOne("SELECT id_materia FROM academia.materia WHERE codigo = UPPER(TRIM(?)) LIMIT 1", [$data['materia_codigo']]);
                if (!$materia) { $skipped++; continue; }

                // Valida turno
                $turno = strtolower($data['turno']);
                if (!in_array($turno, ['manana','tarde','noche'])) { $skipped++; continue; }

                $cap = (int)$data['capacidad'];
                if ($cap <= 0) { $skipped++; continue; }

                // Normaliza paralelo
                $par = strtoupper(trim($data['paralelo']));

                // UPSERT grupo (requiere tu tabla academia.grupo creada)
                $row = DB::selectOne(<<<'SQL'
                  INSERT INTO academia.grupo(gestion_id, materia_id, paralelo, turno, capacidad, estado)
                  VALUES(?,?,?,?,?,'ACTIVO')
                  ON CONFLICT (gestion_id, materia_id, UPPER(TRIM(paralelo)))
                  DO UPDATE SET turno=EXCLUDED.turno, capacidad=EXCLUDED.capacidad
                  RETURNING xmax = 0 AS inserted
                SQL, [(int)$req->gestion_id, (int)$materia->id_materia, $par, $turno, $cap]);

                if ($row && $row->inserted) $inserted++; else $updated++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $errors++;
        }

        Bitacora::log(optional(auth()->user())->id_persona, 'CPD', 'ImportarOferta', 'confirmar', "Gestion:{$req->gestion_id}", 'OK', null, ['inserted'=>$inserted,'updated'=>$updated,'skipped'=>$skipped]);

        return response()->json(['data' => compact('inserted','updated','skipped','errors')]);
    }

    // -------- helpers --------

    private function previewCsv(string $path): array
    {
        $f = fopen($path, 'r');
        $header = fgetcsv($f);
        if (!$header) throw new \RuntimeException('CSV vacío');

        $header = array_map(fn($s)=>trim((string)$s), $header);
        $missing = array_values(array_diff(self::REQUIRED, $header));
        if ($missing) throw new \RuntimeException('Faltan columnas: '.implode(', ', $missing));

        $idx = array_flip($header);
        $rows = []; $ok=0; $warn=0; $err=0; $rowNum=1;

        while (($cols = fgetcsv($f)) !== false) {
            $rowNum++;
            $rec = [];
            foreach (self::REQUIRED as $c) { $rec[$c] = trim((string)($cols[$idx[$c]] ?? '')); }

            $status = 'ok'; $message = null;
            if ($rec['carrera_sigla']==='' || $rec['materia_codigo']==='' || $rec['paralelo']==='') {
                $status='error'; $message='Campos obligatorios vacíos (carrera_sigla, materia_codigo, paralelo).';
            } elseif (!in_array(strtolower($rec['turno']), ['manana','tarde','noche'])) {
                $status='error'; $message='turno inválido (manana|tarde|noche).';
            } elseif (!preg_match('/^\d+$/', $rec['capacidad']) || (int)$rec['capacidad']<=0) {
                $status='error'; $message='capacidad debe ser entero > 0.';
            }

            if ($status==='ok') $ok++;
            if ($status==='warn') $warn++;
            if ($status==='error') $err++;

            $rows[] = ['rowNum'=>$rowNum, 'data'=>$rec, 'status'=>$status, 'message'=>$message];
        }
        fclose($f);

        $summary = ['total'=>count($rows), 'ok'=>$ok, 'warn'=>$warn, 'error'=>$err];
        return [$rows,$summary];
    }
}
