<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Bao cao nang luc lop {{ $class->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.5;
            color: #111827;
            margin: 0;
            background: #fff;
        }
        .page { padding: 16mm 17mm; }
        .header {
            border-bottom: 2pt solid #1f2937;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .title {
            font-size: 19pt;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .subtitle { color: #4b5563; font-size: 9.5pt; }
        .meta {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 16px;
        }
        .meta td {
            border: 0.75pt solid #d1d5db;
            padding: 6px 8px;
            vertical-align: top;
        }
        .meta .label {
            width: 118px;
            background: #f3f4f6;
            color: #374151;
            font-weight: 700;
        }
        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .summary td {
            border: 0.75pt solid #d1d5db;
            padding: 8px;
            text-align: center;
        }
        .summary .number {
            font-size: 16pt;
            font-weight: 700;
            display: block;
            color: #111827;
        }
        .summary .label {
            color: #6b7280;
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .student {
            border: 0.75pt solid #d1d5db;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }
        .student-head {
            background: #1f2937;
            color: #fff;
            padding: 7px 9px;
            font-weight: 700;
        }
        .student-meta {
            width: 100%;
            border-collapse: collapse;
        }
        .student-meta td {
            border-bottom: 0.75pt solid #e5e7eb;
            padding: 6px 9px;
            font-size: 9pt;
        }
        .student-meta strong { color: #374151; }
        .content { padding: 8px 10px 10px; }
        .section-title {
            font-weight: 700;
            color: #111827;
            margin: 8px 0 4px;
            font-size: 9.5pt;
        }
        .summary-text {
            background: #f9fafb;
            border-left: 3pt solid #2563eb;
            padding: 7px 9px;
            margin-bottom: 7px;
        }
        ul {
            margin: 0 0 7px 16px;
            padding: 0;
        }
        li { margin-bottom: 3px; }
        .empty {
            color: #6b7280;
            font-style: italic;
        }
        .missing {
            padding: 10px;
            color: #92400e;
            background: #fffbeb;
        }
        .footer {
            border-top: 1pt solid #d1d5db;
            margin-top: 18px;
            padding-top: 7px;
            color: #6b7280;
            font-size: 8pt;
            text-align: right;
        }
    </style>
</head>
<body>
@php
    $totalStudents = $students->count();
    $studentsWithReports = $students->filter(fn($item) => !empty($item['report']))->count();
    $averageScore = $students
        ->pluck('report.average_score')
        ->filter(fn($score) => $score !== null)
        ->avg();
@endphp
<div class="page">
    <div class="header">
        <div class="title">Bao cao danh gia nang luc lop</div>
        <div class="subtitle">Tong hop diem manh, diem can ho tro va khuyen nghi cho tung hoc sinh</div>
    </div>

    <table class="meta">
        <tr>
            <td class="label">Lop</td>
            <td>{{ $class->name }} @if($class->code) ({{ $class->code }}) @endif</td>
            <td class="label">Giao vien</td>
            <td>{{ $class->teacher?->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Hoc ky</td>
            <td>{{ $class->semester ?: 'N/A' }}</td>
            <td class="label">Ngay xuat</td>
            <td>{{ $generated_at->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td>
                <span class="number">{{ $totalStudents }}</span>
                <span class="label">Hoc sinh active</span>
            </td>
            <td>
                <span class="number">{{ $studentsWithReports }}</span>
                <span class="label">Da co report</span>
            </td>
            <td>
                <span class="number">{{ $averageScore !== null ? number_format($averageScore, 1) . '%' : 'N/A' }}</span>
                <span class="label">Diem TB report</span>
            </td>
        </tr>
    </table>

    @foreach($students as $item)
        @php
            $student = $item['student'];
            $report = $item['report'];
        @endphp
        <div class="student">
            <div class="student-head">{{ $loop->iteration }}. {{ $student?->name ?? 'Hoc sinh khong xac dinh' }}</div>
            <table class="student-meta">
                <tr>
                    <td><strong>Email:</strong> {{ $student?->email ?? 'N/A' }}</td>
                    <td><strong>Diem TB:</strong> {{ $report?->average_score !== null ? $report->average_score . '%' : 'N/A' }}</td>
                    <td><strong>Quiz:</strong> {{ $report?->total_quizzes_taken ?? 0 }}</td>
                    <td><strong>Bai tap:</strong> {{ $report?->total_assignments_completed ?? 0 }}</td>
                </tr>
            </table>

            @if(!$report)
                <div class="missing">Chua co bao cao AI cho hoc sinh nay.</div>
            @else
                <div class="content">
                    <div class="section-title">Tong quan</div>
                    <div class="summary-text">{{ $report->overall_summary ?: 'Chua co nhan xet tong quan.' }}</div>

                    <div class="section-title">Diem manh</div>
                    @if(!empty($report->strengths))
                        <ul>
                            @foreach($report->strengths as $strength)
                                <li>{{ $strength }}</li>
                            @endforeach
                        </ul>
                    @else
                        <div class="empty">Chua co du lieu.</div>
                    @endif

                    <div class="section-title">Diem can ho tro</div>
                    @if(!empty($report->weaknesses))
                        <ul>
                            @foreach($report->weaknesses as $weakness)
                                <li>{{ $weakness }}</li>
                            @endforeach
                        </ul>
                    @else
                        <div class="empty">Chua co du lieu.</div>
                    @endif

                    <div class="section-title">Khuyen nghi</div>
                    @if(!empty($report->recommendations))
                        <ul>
                            @foreach($report->recommendations as $recommendation)
                                <li>{{ $recommendation }}</li>
                            @endforeach
                        </ul>
                    @else
                        <div class="empty">Chua co du lieu.</div>
                    @endif
                </div>
            @endif
        </div>
    @endforeach

    <div class="footer">Bao cao AI chi dung de tham khao. Giao vien can kiem tra lai truoc khi su dung chinh thuc.</div>
</div>
</body>
</html>
