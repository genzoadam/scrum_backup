<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Team;
use Yajra\Datatables\Html\Builder;
use Yajra\Datatables\Datatables;
use App\TeamUser;
use Session;
use Excel; 
use Illuminate\Support\Facades\Auth;
use Validator;


class TeamsController extends Controller
{
   
     public function index(Request $request, Builder $htmlBuilder)
    {
        if ($request->ajax()) {
            $teams = Team::select(['id', 'kode_team', 'nama_team']);
            return Datatables::of($teams)
                ->addColumn('nama_team', function($team) {
                    return '<a href="'.route('teams.show', $team->id).'">'.$team->nama_team.'</a>';
                })

                ->addColumn('action', function($team){
                    return view('datatable._action', [
                        'model' => $team,
                        'form_url' => route('teams.destroy', $team->id),
                        'edit_url' => route('teams.edit', $team->id),
                        'confirm_message' => 'Apakah anda yakin akan menghapus ' . $team->nama_team . '?'
                    ]);

                })->make(true);

                 // ->addColumn('lists', function($team){
                 //    return view('datatable.lists', [
                 //        'model' => $team, 
                 //        'lists_url' => route('teams.lists', $team->id),
                 //        'confirm_message' => 'Apakah Anda Yakin akan me-reset password ' . $team->nama_team . ' ?',
                 //    ]);
                    
                // })
        }

        $html = $htmlBuilder
            ->addColumn(['data' => 'kode_team', 'kode_team'=>'kode_team', 'title'=>'Kode Team'])
            ->addColumn(['data' => 'nama_team', 'nama_team'=>'nama_team', 'title'=>'Nama Team'])
            ->addColumn(['data' => 'action', 'name'=>'action', 'title'=>'Aksi', 'orderable'=>false, 'searchable'=>false]);
            // ->addColumn(['data' => 'lists', 'name'=>'lists', 'title'=>'Anggota', 'orderable'=>false, 'searchable'=>false]);

           
        return view('teams.index')->with(compact('html'));
    }

    public function create()
    {
        
        return view('teams.create');

    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'kode_team' => 'required|unique:teams,kode_team',
            'nama_team' => 'required|unique:teams,nama_team'
        ]);
        $team = Team::create($request->all());
        Session::flash("flash_notification", [
            "level"=>"success",
            "message"=>"Berhasil menyimpan " .$team->nama_team.""
        ]);
        return redirect()->route('teams.index');
    }

    public function show($id)
    {
         $team = Team::find($id);
         $anggotaTeam = TeamUser::where('team_id', $id)->get();
        return view('teams.show')->with(compact('team', 'anggotaTeam'));
    }

    public function edit($id)
    {
        $team = Team::find($id);
        return view('teams.edit')->with(compact('team'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'kode_team' => 'required|unique:teams,kode_team,' . $id,
            'nama_team' => 'required|unique:teams,nama_team,' . $id
        ]);
        $team = Team::find($id);
        $team->update(['kode_team' => $request->kode_team,'nama_team' => $request->nama_team]);
        Session::flash("flash_notification", [
        "level"=>"success",
        "message"=>"Berhasil Mengedit ".$team->nama_team.""
        ]);                             
        return redirect()->route('teams.index');

    }

    public function destroy($id)
    {
         $data_teamuser = TeamUser::where('team_id', $id)->count();

        //JIKA TEAM SUDAH TERPAKAI
        if ($data_teamuser > 0) {

            //PERINGTAN TIDAK BISA DIHAPUS
            Session::flash("flash_notification", [
            "level"=>"danger",
            "message"=>"Team Tidak Bisa dihapus. Karena Sudah Terpakai."
            ]);

            return redirect()->route('teams.index');

        }
        else{

            //membuat proses hapus
            Team::destroy($id);
            Session::flash("flash_notification", [
            "level"=>"success",
            "message"=>"Team berhasil dihapus"
            ]);
            return redirect()->route('teams.index');

        }

    }

    public function export() { 
        return view('teams.export');
    }
     public function exportPost(Request $request){ 
        $request->validate([ 
            'team_id' => 'required', 
        ], [ 
            'team_id.required' => 'Silahkan pilih minimal satu aplikasi.' 
        ]); 
        $team = Team::whereIn('id', $request->get('team_id'))->get(); 
        Excel::create('Data Master Data Team', function($excel) use ($team){ 
            $excel->setTitle('Data Master Data Team') 
            ->setCreator(Auth::user()->name); 
            $excel->sheet('Data Team', function($sheet) use ($team){ 
                $row = 1; 
                $sheet->row($row,[ 
                    'Kode Team', 
                    'Nama Team' 
                ]); 
            foreach ($team as $app) { 
                $sheet->row(++$row, [ 
                    $app->kode_team, 
                    $app->nama_team 
                ]); 
            } 
            }); 
        })->export('xls'); 
    } 

    public function generateExcelTemplate() { 
        Excel::create('Template Import Team', function($excel) {
        // Set the properties
            $excel->setTitle('Template Import Team')
                ->setCreator('Team')
                ->setCompany('Team')
                ->setDescription('Template import buku untuk Team');

        $excel->sheet('Data Team', function($sheet) {
            $row = 1;
            $sheet->row($row, [
                'Kode Team',
                'Nama Team',
            ]);
        });

        })->export('xlsx');
    }

    // public function importExcel(Request $request) {
    //     // validasi untuk memastikan file yang diupload adalah excel
    //     $this->validate($request, [ 'excel' => 'required|mimes:xls,xlsx' ]);

    //     // ambil file yang baru diupload
    //     $excel = $request->file('excel');
        
    //     // baca sheet pertama
    //     $excels = Excel::selectSheetsByIndex(0)->load($excel, function($reader) {
        
    //     // options, jika ada
    //     })->get();
        
    //     // rule untuk validasi setiap row pada file excel
    //     $rowRules = [
    //         'Kode Team' => 'required',
    //         'Nama Team' => 'required',
    //     ];
        
    //     // Catat semua id buku baru
    //     // ID ini kita butuhkan untuk menghitung total buku yang berhasil diimport
    //     $teams_id = [];
        
    //     // looping setiap baris, mulai dari baris ke 2 (karena baris ke 1 adalah nama kolom)
    //     foreach ($excels as $row) {
        
    //     // Membuat validasi untuk row di excel
    //     // Disini kita ubah baris yang sedang di proses menjadi array
    //     $validator = Validator::make($row->toArray(), $rowRules);
        
    //     // Skip baris ini jika tidak valid, langsung ke baris selanjutnya
    //     if ($validator->fails()) continue;
        
    //     // buat buku baru
    //     $team = Team::create([
    //         'kode_team' => $row['Kode Team'],
    //         'nama_team' => $row['Nama Team']
    //     ]);

    //     // catat id dari buku yang baru dibuat
    //     array_push($teams_id, $team->id);
    //     }
        
    //     // Ambil semua buku yang baru dibuat
    //     $teams = Team::whereIn('id', $teams_id)->get();
        
    //     // redirect ke form jika tidak ada buku yang berhasil diimport
    //     if ($teams->count() == 0) {
    //         Session::flash("flash_notification", [
    //             "level" => "danger",
    //             "message" => "Tidak ada team yang berhasil diimport."
    //         ]);
    //         return redirect()->back();
    //     }

    //     // set feedback
    //     Session::flash("flash_notification", [
    //         "level" => "success",
    //         "message" => "Berhasil mengimport " . $teams->count() . " buku."
    //     ]);

    //     // Tampilkan index buku
    //     return redirect()->route('teams.index');
    // }


    public function importExcel(Request $request) {
      //validasi untuk memastikan file yang diupload adalah excel
    $this->validate($request, ['excel'=>'required|mimes:xls,xlsx']);
    //ambil file yang baru di upload
    $excel = $request->file('excel');
    //baca sheet pertama
    $excels = Excel::selectSheetsByIndex(0)->load($excel,function($reader){
      //option ,jika ada
    })->get();


   //rule untuk validasi setiap row pada file excel
    $rowRules = [
      'kode team' => 'required',
      'nama team'  => 'required',
    ];

   //Catat semua id stok awal baru
    //ID ini kita butuhkan untuk menghitung total stokawal yang berhasil di import
    $teams_id = [];

   //looping setiap baris ,mulai dari baris ke 2 (karena baris ke 1 adlah nama kolom )
    foreach ($excels as $row) {
      //membuat validasi untuk row di excel
      //Dsini kita ubah baris yang sedang di proses menjadi array
      $validator = Validator::make($row->toArray(),$rowRules);

     //Skip baris ini jadi tidak valid , langsung ke baris selajutnya
      if ($validator->fails()) continue;

     //Sintax di bawah di eksekusi jika baris excel ini valid

     // //Cek apakah produk sudah terdaftar di database
     //  $team = Team::where('kode_team',$row['kode_team'])->first();
     //  //buat penulis jika belum ada
     //  if (!$team){
     //    $team = Team::create([
     //        'kode_team',$row['kode_team'
     //    ]]);
     //  }

     //buat stok wal baru
      $team = Team::create([
          'kode_team' => $row['Kode Team'],
         'nama_team' => $row['Nama Team'],

     ]);

     //catat id dari stokawal yang baru dibuat
      array_push($teams_id, $team->id);

   }

   //ambil semua stokawal yang baru dibuat
    $teams = Team::whereIn('id',$teams_id)->get();

   //redirect ke form jika tidak ada stokawal yang berhasil di import
    if($teams->count() == 0){
      Session::flash('flash_notification',[
        'level' =>'danger',
        'message'=>'Tidak ada Team yang diimport'

     ]);
      return redirect()->back();
    }

   //set feedback
    Session::flash('flash_notification',[
        'level' =>'success',
        'message'=>"Berhasil mengimport ".$teams->count()." Stok awal"

   ]);

   //Tampilkan index stokawal
    return redirect()->route('teams.index');
    }

}

