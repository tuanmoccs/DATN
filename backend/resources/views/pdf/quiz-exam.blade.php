<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $quiz->title }} — Examination Paper</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            font-size: 10.5pt;
            line-height: 1.5;
            color: #0d0d0d;
            background: #fff;
        }

        /* ── PAGE ── */
        .page {
            width: 100%;
            padding: 16mm 18mm 18mm 18mm;
        }

        /* ── INSTITUTION HEADER ── */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 3px double #0d0d0d;
            margin-bottom: 12px;
        }

        .header-table td {
            padding: 2px 0 10px 0;
            vertical-align: top;
        }

        .header-left {
            font-size: 10pt;
            color: #2c2c2c;
        }

        .header-left .dept {
            font-weight: 700;
            font-size: 11pt;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #0d0d0d;
        }

        .header-right {
            text-align: right;
            font-size: 9pt;
            color: #5a5a5a;
            line-height: 1.7;
        }

        .header-right strong {
            color: #0d0d0d;
        }

        /* ── TITLE BLOCK ── */
        .title-block {
            text-align: center;
            margin: 14px 0 12px;
        }

        .exam-label {
            font-size: 8.5pt;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: #5a5a5a;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .exam-title {
            font-family: 'DejaVu Serif', Georgia, serif;
            font-size: 20pt;
            font-weight: 700;
            color: #0d0d0d;
            line-height: 1.2;
            margin-bottom: 4px;
        }

        .exam-subtitle {
            font-size: 9.5pt;
            color: #5a5a5a;
            font-style: italic;
        }

        /* ── META TABLE ── */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 9.5pt;
        }

        .meta-table td {
            padding: 5px 9px;
            border: 0.75pt solid #c0c0c0;
            vertical-align: middle;
        }

        .meta-table .lbl {
            background-color: #1a3a5c;
            color: #ffffff;
            font-weight: 700;
            font-size: 8pt;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            width: 110px;
            white-space: nowrap;
        }

        .meta-table .val {
            color: #2c2c2c;
        }

        /* ── STUDENT BOX ── */
        .student-box {
            border: 1.25pt solid #0d0d0d;
            padding: 10px 14px 6px;
            margin: 12px 0 16px;
        }

        .student-table {
            width: 100%;
            border-collapse: collapse;
        }

        .student-table td {
            padding: 0 12px 0 0;
            vertical-align: bottom;
        }

        .student-table td:last-child {
            padding-right: 0;
        }

        .field-label {
            font-size: 7.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #5a5a5a;
            display: block;
            margin-bottom: 5px;
        }

        .field-line {
            border-bottom: 1pt solid #0d0d0d;
            height: 16px;
            width: 100%;
            display: block;
        }

        /* ── INSTRUCTIONS ── */
        .instructions {
            background-color: #f3f3ef;
            border-left: 3pt solid #1a3a5c;
            padding: 8px 13px;
            margin-bottom: 16px;
            font-size: 9.5pt;
            color: #2c2c2c;
        }

        .inst-title {
            display: block;
            font-size: 8.5pt;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 5px;
            color: #1a3a5c;
            font-weight: 700;
        }

        .instructions ol {
            padding-left: 15px;
            margin: 0;
        }

        .instructions li {
            margin-bottom: 2px;
        }

        /* ── SECTION HEADER ── */
        .section-header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1pt solid #1a3a5c;
            margin: 16px 0 10px;
        }

        .section-header-table td {
            padding: 0 0 4px 0;
            vertical-align: bottom;
        }

        .section-tag {
            display: inline-block;
            background-color: #1a3a5c;
            color: #ffffff;
            font-size: 7.5pt;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 3px 10px;
        }

        .section-info {
            text-align: right;
            font-size: 8.5pt;
            color: #5a5a5a;
        }

        /* ── QUESTIONS ── */
        .question {
            margin-bottom: 18px;
            page-break-inside: avoid;
        }

        .question-header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        .question-header-table td {
            padding: 0;
            vertical-align: top;
        }

        .q-num {
            font-family: 'DejaVu Serif', Georgia, serif;
            font-size: 11.5pt;
            font-weight: 700;
            color: #1a3a5c;
            width: 28px;
            white-space: nowrap;
        }

        .q-text {
            font-size: 11pt;
            font-weight: 600;
            color: #0d0d0d;
            line-height: 1.5;
        }

        .q-pts {
            text-align: right;
            white-space: nowrap;
            width: 48px;
            font-size: 8pt;
            color: #777777;
            border: 0.5pt solid #cccccc;
            padding: 1px 5px;
        }

        /* ── OPTIONS ── */
        .options-table {
            width: 100%;
            border-collapse: collapse;
            margin-left: 28px;
            margin-top: 4px;
        }

        .options-table td {
            padding: 2px 10px 2px 0;
            vertical-align: top;
            width: 50%;
            font-size: 10pt;
            color: #2c2c2c;
            line-height: 1.5;
        }

        .bubble {
            display: inline-block;
            width: 15px;
            height: 15px;
            border: 1.25pt solid #0d0d0d;
            border-radius: 50%;
            text-align: center;
            line-height: 13px;
            font-size: 8pt;
            font-weight: 700;
            color: #0d0d0d;
            margin-right: 5px;
            vertical-align: middle;
        }

        .question-divider {
            border: none;
            border-top: 0.5pt dashed #d0d0d0;
            margin-top: 12px;
        }

        /* ── FOOTER ── */
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 28px;
            border-top: 1.5pt solid #0d0d0d;
        }

        .footer-table td {
            padding: 7px 0 0;
            font-size: 8.5pt;
            color: #5a5a5a;
            vertical-align: top;
        }

        .score-box {
            border: 0.75pt solid #c0c0c0;
            padding: 4px 10px;
            text-align: center;
        }

        .score-box .slbl {
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #777777;
            display: block;
            margin-bottom: 4px;
        }

        .score-line {
            border-bottom: 1pt solid #0d0d0d;
            height: 16px;
            width: 80px;
        }

        @media print {
            .page { padding: 12mm 16mm 14mm 16mm; }
            .question { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
<div class="page">

    {{-- ══ INSTITUTION HEADER ══ --}}
    <table class="header-table">
        <tr>
            <td class="header-left">
                <div class="dept">Department of Education &amp; Training</div>
                <div>{{ $quiz->school_name ?? 'TUANANH' }}</div>
                <div>Academic Year {{ $quiz->academic_year ?? date('Y') . '–' . (date('Y') + 1) }}</div>
            </td>
            <td class="header-right">
                <strong>Code:</strong> {{ $quiz->code ?? 'EXAM-' . str_pad($quiz->id ?? 0, 4, '0', STR_PAD_LEFT) }}<br>
                <strong>Date:</strong> {{ date('d / m / Y') }}<br>
                <strong>Form:</strong> Written Examination
            </td>
        </tr>
    </table>

    {{-- ══ EXAM TITLE ══ --}}
    <div class="title-block">
        <div class="exam-label">Official Examination Paper</div>
        <div class="exam-title">{{ $quiz->title }}</div>
        @if(!empty($quiz->description))
            <div class="exam-subtitle">{{ $quiz->description }}</div>
        @endif
    </div>

    {{-- ══ META TABLE ══ --}}
    @php $totalPts = collect($quiz->questions ?? [])->sum(fn($q) => $q->points ?? 1); @endphp
    <table class="meta-table">
        <tr>
            <td class="lbl">Subject</td>
            <td class="val">{{ $quiz->subject ?: '—' }}</td>
            <td class="lbl">Duration</td>
            <td class="val">{{ $quiz->time_limit ? $quiz->time_limit . ' minutes' : 'Unlimited' }}</td>
        </tr>
        <tr>
            <td class="lbl">Grade / Level</td>
            <td class="val">{{ $quiz->grade ?: '—' }}</td>
            <td class="lbl">Total Questions</td>
            <td class="val">{{ count($quiz->questions ?? []) }} questions</td>
        </tr>
        <tr>
            <td class="lbl">Total Score</td>
            <td class="val" colspan="3">{{ $totalPts }} points</td>
        </tr>
    </table>

    {{-- ══ STUDENT INFO ══ --}}
    <div class="student-box">
        <table class="student-table">
            <tr>
                <td style="width:32%">
                    <span class="field-label">Full Name</span>
                    <span class="field-line"></span>
                </td>
                <td style="width:24%">
                    <span class="field-label">Student ID</span>
                    <span class="field-line"></span>
                </td>
                <td style="width:24%">
                    <span class="field-label">Class</span>
                    <span class="field-line"></span>
                </td>
                <td style="width:20%">
                    <span class="field-label">Exam Room</span>
                    <span class="field-line"></span>
                </td>
            </tr>
        </table>
    </div>

    {{-- ══ INSTRUCTIONS ══ --}}
    <div class="instructions">
        <span class="inst-title">Instructions</span>
        <ol>
            <li>Read each question carefully before answering.</li>
            <li>For multiple-choice questions, circle <strong>one</strong> answer only unless stated otherwise.</li>
            <li>Write legibly. Marks may be deducted for illegible answers.</li>
            <li>No electronic devices are permitted unless specified by the examiner.</li>
            <li>Return this paper to the examiner upon completion.</li>
        </ol>
    </div>

    {{-- ══ SECTION HEADER ══ --}}
    <table class="section-header-table">
        <tr>
            <td><span class="section-tag">Multiple Choice</span></td>
            <td class="section-info">{{ count($quiz->questions ?? []) }} questions &mdash; {{ $totalPts }} pts total</td>
        </tr>
    </table>

    {{-- ══ QUESTIONS ══ --}}
    @foreach($quiz->questions ?? [] as $question)
        @php $opts = collect($question->options ?? []); @endphp
        <div class="question">

            <table class="question-header-table">
                <tr>
                    <td class="q-num">{{ $loop->iteration }}.</td>
                    <td class="q-text">{{ $question->content }}</td>
                    <td class="q-pts">{{ $question->points ?? 1 }} pt{{ ($question->points ?? 1) != 1 ? 's' : '' }}</td>
                </tr>
            </table>

            <table class="options-table">
                @foreach($opts->chunk(2) as $pair)
                    <tr>
                        @foreach($pair as $i => $opt)
                            <td>
                                <span class="bubble">{{ chr(65 + $i) }}</span>{{ $opt->option_text }}
                            </td>
                        @endforeach
                        @if($pair->count() === 1)
                            <td></td>
                        @endif
                    </tr>
                @endforeach
            </table>

            @if(!$loop->last)
                <hr class="question-divider">
            @endif
        </div>
    @endforeach

    {{-- ══ FOOTER ══ --}}
    <table class="footer-table">
        <tr>
            <td style="width:40%">{{ $quiz->title }} &mdash; Examination Paper</td>
            <td style="width:30%; text-align:center;">— End of Paper —</td>
            <td style="width:30%; text-align:right;">
                <div class="score-box">
                    <span class="slbl">Examiner's Score</span>
                    <div class="score-line"></div>
                </div>
            </td>
        </tr>
    </table>

</div>
</body>
</html>