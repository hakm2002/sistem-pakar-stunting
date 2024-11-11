@extends('clients.cl_main')
@section('title', 'Form Diagnosa')

@section('cl_content')

    <div class="container">
        <div class="row mt-5">
            <div class="card p-5">
                <h1 class="text-center">Hasil Deteksi Kamu</h1>
                <img src="{{asset('assets/img/hasil2.jpg')}}" alt="" width="300px" class="d-block mx-auto my-3">
                <p class="text-center">Berdasarkan data gejala yang diinputkan, hasil perhitungan sistem menunjukkan kemungkinan Anda mengalami Stunting adalah sebesar.</p>
                @php
                $value = round(($hasil["value"] * 100), 2);
                @endphp

                @if ($value >= 100)
                    <h3 class="text-center"> {{$value}}% | Pasti Stunting</h3>
                @elseif ($value < 100 && $value >=80)
                    <h3 class="text-center"> {{$value}}% | Hampir Pasti Stunting</h3>
                @elseif ($value < 80 && $value > 60)
                    <h3 class="text-center"> {{$value}}% | Kemungkinan Besar Stunting</h3>
                @elseif ($value < 60 && $value > 0)
                    <h3 class="text-center"> {{$value}}% | Kemungkinan Kecil Stunting</h3>
                @elseif ($value < 0)
                    <h3 class="text-center"> {{$value}}% | Bukan Stunting</h3>
                @endif
                
            </div>
        </div>
       <div class="row mx-auto my-4">
        <div class="col-lg-10 mx-auto">

            <table class="table table-hover">
                <thead>
                  <tr>
                    <th scope="col">#</th>
                    <th scope="col">Diagnosa ID</th>
                    <th scope="col">Tingkat Depresi</th>
                    <th scope="col">Persentase</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <th scope="row">1</th>
                    <td>{{ $diagnosa->diagnosa_id }}</td>
                    <td> {{ ($value > 0) ? 'P01 | Stunting' : 'P02 | Bukan Stunting'  }}</td>
                    <td>{{ $value }} %</td>
                  </tr>
                </tbody>
            </table>
        </div>

        {{-- section 2 --}}
        <div class="row">
            <div class="col-lg-12 mx-auto">
                <h1 class="text-center mt-5"> Detail Perhitungan</h1>
                <div class="d-flex ">
                    {{-- Pakar --}}
                    <table class="table table-hover mt-lg-5 border border-primary p-3 mx-3">
                        <thead>
                            <tr>
                                <th scope="col">Pakar</th>
                            </tr>
                            <tr>
                                <th scope="col">No</th>
                                <th scope="col">Gejala</th>
                                <th scope="col">Nilai (MB - MD)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pakar as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        {{ $item->kode_gejala }} | {{ $item->kode_depresi }}
                                    </td>
                                    <td>{{ $item->mb - $item->md }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- User --}}
                    <table class="table table-hover mt-lg-5 border border-danger p-3 mx-3">
                        <thead>
                            <tr>
                                <th scope="col">User</th>
                            </tr>
                            <tr>
                                <th scope="col">Gejala</th>
                            <th scope="col">Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($gejala_by_user as $key)
                            <tr>
                                <td>{{ $key[0] }}</td>
                                <td>{{ $key[1] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- Tabel Cf Gabungan --}}
                    {{-- CF Gabungan --}}
                    <table class="table table-hover mt-lg-5 border border-info p-3 mx-3">
                        <thead>
                            <tr>
                                <th scope="col">Hasil</th>
                            </tr>
                            <tr>
                                <th scope="col">Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cf_kombinasi["cf"] as $key)
                            <tr>
                                <td>{{ $key }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- section 3 --}}
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card my-4">
                    <div class="card-header">
                      Hasil
                    </div>
                    <div class="card-body">
                      <h5 class="card-title">
                        {{ $diagnosa_dipilih["kode_depresi"]->kode_depresi }} | {{ $diagnosa_dipilih["kode_depresi"]->depresi }}
                        </h5>
                      <p class="card-text"><span class="fw-semibold fs-4">{{ round(($hasil["value"] * 100), 2) }}</span> %</p>
                      {{-- <a href="#" class="btn btn-primary">Go somewhere</a> --}}
                    </div>
                  </div>
            </div>
        </div>
        @include('components.cl_article')
        <div >
            <a style="align-content: flex-end" href="/dashboard" class="btn btn-primary"> KEMBALI</a>
        </div>
       </div>
    </div>
@endsection
