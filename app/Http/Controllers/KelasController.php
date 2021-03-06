<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Materi;
use App\Models\PengajuanKelas;
use App\Models\Post;
use App\Models\RoleKelas;
use App\Models\Tugas;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class KelasController extends Controller
{
    public function index(Request $request)
    {
        $kelas = RoleKelas::with('kelas')->where('user_id', $request->id)->get();

        return response()->json($kelas, 201);
    }

    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'nama'          => ['required'],
                'deskripsi'     => ['required'],
                'tipe'          => ['required', 'in:1,2']
            ],
            [
                'required'      => 'Mohon isi field :attribute',
                'in'            => ':attribute tidak sesuai'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()]);
        }

        DB::transaction(function () use ($request) {
            $kelas = Kelas::create(
                [
                    'user_id'   => $request->id,
                    'nama'      => $request->nama,
                    'deskripsi' => $request->deskripsi,
                    'tipe'      => $request->tipe,
                    'kode'      => Str::random(7),
                    'created_at' => Carbon::now()->setTimezone('Asia/Jakarta')->toDateTimeString()
                ]
            );

            RoleKelas::create(
                [
                    'user_id'   => $kelas->user_id,
                    'kelas_id'  => $kelas->id,
                    'role'      => 1
                ]
            );
        });


        return response()->json(['error' => null], 201);
    }

    public function show(Request $request)
    {
        $kelas = Kelas::with('user')->where('id', $request->id)->first();

        $post = Post::with('komentar', 'komentar.user')->where('kelas_id', $kelas->id)->latest()->get();

        $role = RoleKelas::with('user')->where('kelas_id', $kelas->id)->orderBy('role')->get();

        $materi = Materi::with('user')->where('kelas_id', $kelas->id)->get();

        $tugas = Tugas::with('user')->where('kelas_id', $kelas->id)->get();

        $pengajuan = PengajuanKelas::with('user')->where('kelas_id', $kelas->id)->get();

        if ($kelas->tipe == '1') {
            $pengajuan = null;
        }

        $response = array(
            'kelas'     => $kelas,
            'post'      => $post,
            'role'      => $role,
            'materi'    => $materi,
            'tugas'     => $tugas,
            'pengajuan' => $pengajuan
        );

        return response()->json($response, 201);
    }

    public function update(Request $request)
    {
        if (!Kelas::where('id', $request->id)->where('user_id', $request->user)->exist()) {
            return response()->json(['error' => 'Unauthorized']);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'nama'          => ['required'],
                'deskripsi'     => ['required'],
                'tipe'          => ['required', 'in:1,2']
            ],
            [
                'required'      => 'Mohon isi field :attribute',
                'in'            => ':attribute tidak sesuai'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()]);
        }

        Kelas::where('id', $request->id)
            ->update(
                [
                    'nama'      => $request->nama,
                    'deskripsi' => $request->deskripsi,
                    'tipe'      => $request->tipe,
                    'updated_at' => Carbon::now()->setTimezone('Asia/Jakarta')->toDateTimeString()
                ]
            );

        return response()->json(['error' => null]);
    }

    public function destroy(Request $request)
    {
        if (!Kelas::where('id', $request->id)->where('user_id', $request->user)->exist()) {
            return response()->json(['error' => 'Unauthorized']);
        }

        Kelas::where('id', $request->kelas_id)->delete();

        return response()->json(['error' => null], 201);
    }
}
