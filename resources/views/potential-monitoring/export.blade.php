<?php echo "\xEF\xBB\xBF"; ?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="UTF-8">
    <!--[if gte mso 9]>
    {!! '<xml>
        <x' . ':ExcelWorkbook>
            <x' . ':ExcelWorksheets>
                <x' . ':ExcelWorksheet>
                    <x' . ':Name>' . htmlspecialchars($title) . '</x' . ':Name>
                    <x' . ':WorksheetOptions>
                        <x' . ':DisplayGridlines/>
                    </x' . ':WorksheetOptions>
                </x' . ':ExcelWorksheet>
            </x' . ':ExcelWorksheets>
        </x' . ':ExcelWorkbook>
    </xml>' !!}
    <![endif]-->
    <style>
        table { border-collapse: collapse; }
        th, td { border: 1px solid #000000; }
        th { background-color: #f8f9fa; font-weight: bold; }
    </style>
</head>
<body>
    @if($tab === 'data-potensi')
        <table>
            <thead>
                <tr>
                    <th rowspan="2">No</th>
                    <th rowspan="2">Dealer</th>
                    <th rowspan="2">Cabang</th>
                    <th rowspan="2">Bulan dan Tahun</th>
                    <th colspan="6">Potensi</th>
                </tr>
                <tr>
                    <th>UE</th>
                    <th>UAC</th>
                    <th>%CR</th>
                    <th>RP/UE</th>
                    <th>RP/UAC</th>
                    <th>CR Rp</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($potentials as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->dealer }}</td>
                        <td>{{ $item->nama_dealer }}</td>
                        <td><i>belum diinput</i></td>
                        <td>{{ (int) $item->unit_entry }}</td>
                        <td>{{ (int) $item->unit_ac }}</td>
                        <td>{{ (float) ($item->cr_percent ?? 0) }}%</td>
                        <td><i>belum diinput</i></td>
                        <td>{{ (int) $item->rp_uac }}</td>
                        <td>0</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif(!in_array($tab, ['data-potensi', 'kompetisi', 'relasi']))
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Dealer</th>
                    <th>Cabang</th>
                    <th>Bulan dan Tahun</th>
                    <th>Unit Entry (UE)</th>
                    <th>RP / Unit Entry</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($potentialsUnitEntry as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->dealer }}</td>
                        <td>{{ $item->nama_dealer }}</td>
                        <td>{{ $item->period ? \Carbon\Carbon::parse($item->period)->locale('id')->translatedFormat('F Y') : '-' }}</td>
                        <td>{{ (int) $item->unit_entry }}</td>
                        <td>{{ (int) ($item->rp_uac ?? $item->rp_unit_entry) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif($tab === 'kompetisi')
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Dealer</th>
                    <th>Cabang</th>
                    <th>Bulan dan Tahun</th>
                    <th>Kompetitor</th>
                    <th>Insentif</th>
                    <th>Harga</th>
                    <th>Grooming</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->dealer }}</td>
                        <td>{{ $item->nama_dealer }}</td>
                        <td>{{ $item->periode ? \Carbon\Carbon::parse($item->periode)->locale('id')->translatedFormat('F Y') : '-' }}</td>
                        <td>{{ $item->kompetitor ?? $item->Kompetitor }}</td>
                        <td>{{ $item->insentif }}</td>
                        <td>{{ $item->harga }}</td>
                        <td>{{ $item->grooming }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif($tab === 'relasi')
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Dealer</th>
                    <th>Cabang</th>
                    <th>Bulan dan Tahun</th>
                    <th>SA</th>
                    <th>Concern SA</th>
                    <th>SM</th>
                    <th>Concern SM</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->dealer }}</td>
                        <td>{{ $item->nama_dealer }}</td>
                        <td>{{ $item->periode ? \Carbon\Carbon::parse($item->periode)->locale('id')->translatedFormat('F Y') : '-' }}</td>
                        <td>{{ $item->sa }}</td>
                        <td>{{ $item->concern_sa }}</td>
                        <td>{{ $item->sm }}</td>
                        <td>{{ $item->concern_sm }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
