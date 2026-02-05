<?php
// RFID Home - Main menu page
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2026 Maker Nexus

include 'auth_check.php';  // Require authentication

ob_start();
include 'auth_header.php';
$authHeader = ob_get_clean();
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>RFID Reports</title>
  <meta name="robots" content="noindex, nofollow">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="style.css" rel="stylesheet">
  
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      background-color: #f5f5f5;
      margin: 0;
      padding: 0;
    }
    
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
    }
    
    .page-title {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 30px;
      margin: -20px -20px 30px -20px;
      border-radius: 0 0 10px 10px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .page-title h1 {
      margin: 0;
      font-size: 28px;
      font-weight: 600;
    }
    
    .admin-notice {
      background-color: #fff3cd;
      border-left: 4px solid #ffc107;
      padding: 15px;
      margin-bottom: 30px;
      border-radius: 4px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .admin-notice a {
      color: #856404;
      font-weight: bold;
      text-decoration: none;
      border-bottom: 2px solid #ffc107;
    }
    
    .admin-notice a:hover {
      border-bottom-color: #856404;
    }
    
    .reports-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .report-card {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .report-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .report-card h3 {
      margin: 0 0 10px 0;
      color: #333;
      font-size: 20px;
      border-bottom: 3px solid #667eea;
      padding-bottom: 10px;
    }
    
    .permission-tag {
      display: inline-block;
      background-color: #e3f2fd;
      color: #1976d2;
      padding: 4px 12px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
      margin-bottom: 12px;
    }
    
    .report-card ul {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    
    .report-card li {
      padding: 10px 0;
      border-bottom: 1px solid #f0f0f0;
    }
    
    .report-card li:last-child {
      border-bottom: none;
    }
    
    .report-card a {
      color: #667eea;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.2s;
    }
    
    .report-card a:hover {
      color: #764ba2;
      text-decoration: underline;
    }
    
    .permission-inline {
      color: #999;
      font-size: 12px;
      font-style: italic;
      margin-left: 8px;
    }
    
    @media (max-width: 768px) {
      .container {
        padding: 10px;
      }
      
      .page-title {
        margin: -10px -10px 20px -10px;
        padding: 20px;
      }
      
      .page-title h1 {
        font-size: 22px;
      }
      
      .reports-grid {
        grid-template-columns: 1fr;
        gap: 15px;
      }
      
      .report-card {
        padding: 15px;
      }
      
      .report-card h3 {
        font-size: 18px;
      }
    }
  </style>
</head>

<body>
    <?php echo $authHeader; ?>
    
    <div class="container">
        <div class="page-title">
            <h1>RFID Database Reports</h1>
        </div>
        
        
        <div class="reports-grid">
            <div class="report-card">
                <h3>Updating Displays</h3>
                <ul>
                    <li><a href="rfidcurrentcheckinsWithMOD.php">Current CheckIns</a> ... or <a href="rfidcurrentcheckins.php">without MOD</a></li>
                    <li><a href="rfidcurrentstudio.php?studio=wood">Active In Woodshop</a></li>
                </ul>
            </div>
            
            <div class="report-card">
                <h3>Online Visitor Log</h3>
                <ul>
                    <li><a href="https://rfid.makernexuswiki.com/v2/OVLrecentvisitors.php">Last 5 days of visitors</a></li>
                </ul>
            </div>
            
            <div class="report-card">
                <h3>Summary Reports</h3>
                <div class="permission-tag">Admin or MoD</div>
                <ul>        
                    <li><a href="rfidcheckinlog.php">Last 200 CheckIns</a></li>
                    <li><a href="checkinreport.php">Members per Month/Day Summary</a></li>
                    <li><a href="rfidstudiousage.php?startDate=&endDate=">Studio Usage</a></li>
                </ul>
            </div>
            
            <div class="report-card">
                <h3>Detail Reports</h3>
                <div class="permission-tag">Admin or Accounting</div>
                <ul>
                    <li><a href="checkinreportdetail.php">Members per Month, detail</a></li>
                    <li><a href="rfidonemember.php?clientID=59617641">Report on One Client (modify URL)</a></li>
                    <li><a href="rfidlast100members.php">Last 100 active members</a></li>
                    <li><a href="rfidshopusagebyhour.php?startDate=20230101&endDate=20241231">Usage Heat Map</a></li>
                </ul>
            </div>
            
            <div class="report-card">
                <h3>Staff Activity</h3>
                <div class="permission-tag">Admin or Accounting</div>
                <ul>
                    <li><a href="rfidstaffactivity.php">Staff check in/out, 14 day lookback</a></li>
                    <li><a href="rfidstaffactivity.php?startDate=20230901&endDate=20241231">Staff check in/out with date range</a></li>
                </ul>
            </div>
            
            <div class="report-card">
                <h3>Configuration Reports</h3>
                <div class="permission-tag">Admin or MoD</div>
                <ul>
                    <li><a href="rfidreportstaffmod.php">List all people with Staff or MOD</a></li>
                </ul>
            </div>
            
            <div class="report-card">
                <h3>System Debug Reports</h3>
                <div class="permission-tag">Admin only</div>
                <ul>
                    <li><a href="rfidclientactivity.php">Activity by Client</a></li>
                    <li><a href="rfiddevicelog.php">Log by Device</a></li>
                    <li><a href="rfidtop100.php">Last 100 Raw Data</a></li>
                    <li><a href="rfidcheckindebugactivity.php">Overall logging counts</a></li>
                    <li><a href="rfidstudiousagedenied.php?startDate=20220515&endDate=20241231">Studio Usage Denied</a></li>
                    <li><a href="rfiddeniedanyreason.php">Denied for Any Reason</a></li>
                </ul>
            </div>
        </div>
    </div>



</body>

</html>
