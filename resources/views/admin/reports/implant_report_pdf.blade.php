<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Implant Report</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
            background-color: #fff;
        }
        
        .header {
            padding-bottom: 15px;
            border-bottom: 2px solid #4472C4;
            margin-bottom: 20px;
        }
        
        h1 {
            text-align: center;
            font-size: 22px;
            color: #2F5496;
            margin-bottom: 5px;
            font-weight: 700;
        }
        
        .company-info {
            text-align: center;
            margin-bottom: 10px;
            font-size: 10px;
            color: #666;
        }
        
        .report-info {
            text-align: right;
            margin-bottom: 20px;
            font-size: 9px;
            background-color: #F8F9FA;
            padding: 8px;
            border-radius: 4px;
            border-left: 3px solid #4472C4;
        }
        
        .section-header {
            background-color: #4472C4;
            color: white;
            padding: 8px 12px;
            font-size: 14px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        th {
            background-color: #E7E9F0;
            color: #2F5496;
            font-weight: bold;
            text-align: left;
            padding: 6px 4px;
            font-size: 9px;
            border: 1px solid #D9D9D9;
        }
        
        td {
            padding: 4px;
            text-align: left;
            font-size: 8px;
            border: 1px solid #E5E5E5;
            vertical-align: middle;
        }
        
        tr:nth-child(even) {
            background-color: #F8F9FA;
        }
        
        tr:hover {
            background-color: #F0F2F7;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .status-active {
            color: #1E7E34;
            font-weight: bold;
        }
        
        .status-inactive {
            color: #BD2130;
            font-weight: bold;
        }
        
        .warranty-extended {
            color: #0062CC;
            font-weight: bold;
        }
        
        .text-center {
            text-align: center;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8px;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        
        .highlight-cell {
            background-color: #FFF9E6;
        }
        
        .patient-header {
            background-color: #5B9BD5;
            color: white;
        }
        
        .implant-header {
            background-color: #ED7D31;
            color: white;
        }
        
        .lead-header {
            background-color: #70AD47;
            color: white;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Implant Report</h1>
        <div class="company-info">
            <p>E-Spandan Medical Device Management System</p>
        </div>
    </div>
    
    <div class="report-info">
        <p><strong>Generated:</strong> {{ $generatedAt }}</p>
        <p><strong>Total Records:</strong> {{ count($reportData) }}</p>
    </div>
    
    <!-- Main patient and implant information -->
    <div class="section-header patient-header">
        <i class="fas fa-users"></i> Patient Summary Information
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Patient Name</th>
                <th>Phone</th>
                <th>Device Type</th>
                <th>IPG S/N</th>
                <th>Implant Date</th>
                <th>Hospital</th>
                <th>Status</th>
                <th>Warranty</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData as $row)
            <tr>
                <td>{{ $row['Implant_ID'] }}</td>
                <td><strong>{{ $row['Patient_Name'] }}</strong></td>
                <td>{{ $row['Patient_Phone'] }}</td>
                <td>{{ $row['Device_Type'] }}</td>
                <td class="highlight-cell">{{ $row['IPG_Serial_Number'] }}</td>
                <td>{{ $row['Date_of_Implant'] }}</td>
                <td>{{ $row['Hospital'] }}</td>
                <td class="{{ $row['Status_of_Implant'] == 'Active' ? 'status-active' : 'status-inactive' }}">
                    {{ $row['Status_of_Implant'] }}
                </td>
                <td class="{{ $row['Warranty_Type'] == 'Extended' ? 'warranty-extended' : '' }}">
                    {{ $row['Warranty_Type'] }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="page-break"></div>
    
    <!-- Detailed information -->
    <div class="section-header implant-header">
        <i class="fas fa-heartbeat"></i> Detailed Implant Information
    </div>
    <table>
        <thead>
            <tr>
                <th>Implant ID</th>
                <th>Year</th>
                <th>Quarter</th>
                <th>Month</th>
                <th>IPG Model</th>
                <th>IPG Serial</th>
                <th>State</th>
                <th>City</th>
                <th>Channel Partner</th>
                <th>Physician</th>
                <th>CSP</th>
                <th>Extra Lead</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData as $row)
            <tr>
                <td>{{ $row['Implant_ID'] }}</td>
                <td class="text-center">{{ $row['Year'] }}</td>
                <td class="text-center">{{ $row['Quarter'] }}</td>
                <td>{{ $row['Month'] }}</td>
                <td><strong>{{ $row['IPG_Model_Name'] }}</strong></td>
                <td class="highlight-cell">{{ $row['IPG_Serial_Number'] }}</td>
                <td>{{ $row['State'] }}</td>
                <td>{{ $row['City'] }}</td>
                <td>{{ $row['Distributor_Name'] }}</td>
                <td><strong>{{ $row['Physician_Name'] }}</strong></td>
                <td class="text-center">{{ $row['CSP'] }}</td>
                <td class="text-center">{{ $row['Extra_Lead'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="page-break"></div>
    
    <!-- Lead Information -->
    <div class="section-header lead-header">
        <i class="fas fa-plug"></i> Lead Information
    </div>
    <table>
        <thead>
            <tr>
                <th>Implant ID</th>
                <th>Patient</th>
                <th>Lead 1 Model</th>
                <th>Lead 1 S/N</th>
                <th>Lead 2 Model</th>
                <th>Lead 2 S/N</th>
                <th>Lead 3 Model</th>
                <th>Lead 3 S/N</th>
                <th>CSP Lead Model</th>
                <th>CSP Lead S/N</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData as $row)
            <tr>
                <td>{{ $row['Implant_ID'] }}</td>
                <td><strong>{{ $row['Patient_Name'] }}</strong></td>
                <td>{{ $row['Lead_1_Model_Name'] }}</td>
                <td class="highlight-cell">{{ $row['Lead_1_Serial_Number'] }}</td>
                <td>{{ $row['Lead_2_Model_Name'] }}</td>
                <td class="highlight-cell">{{ $row['Lead_2_Serial_Number'] }}</td>
                <td>{{ $row['Lead_3_Model_Name'] }}</td>
                <td class="highlight-cell">{{ $row['Lead_3_Serial_Number'] }}</td>
                <td>{{ $row['CSP_Lead_Model_Name'] }}</td>
                <td class="highlight-cell">{{ $row['CSP_Lead_Serial_Number'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="footer">
        <p>E-Spandan Implant Report | {{ $generatedAt }} | Confidential Medical Information</p>
        <p>Page <span class="page"></span> of <span class="topage"></span></p>
    </div>
</body>
</html>