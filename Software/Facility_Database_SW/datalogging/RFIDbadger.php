<?php
// RFID Badger - Badge Management Tool
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2026 Maker Nexus

include 'auth_check.php';  // Require authentication

// Only MoD, Manager, and Admin can access this page
requireRole(['MoD', 'manager', 'admin']);

ob_start();
include 'auth_header.php';
$authHeader = ob_get_clean();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RFID Badger - Badge Management</title>
    <link href="style.css" rel="stylesheet">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 900px;
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
            margin: 0 0 10px 0;
            font-size: 28px;
            font-weight: 600;
        }
        
        .device-info {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px 15px;
            margin: 0;
            font-weight: 500;
            color: #856404;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .device-info-text {
            flex: 0 0 50%;
        }
        
        .device-info .btn {
            padding: 8px 16px;
            font-size: 14px;
            margin: 0;
        }
        
        .section {
            background: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .section h2 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 20px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input[type="text"] {
            width: 100%;
            max-width: 180px;
            padding: 10px 12px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }
        
        .form-group input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group input[type="text"]:read-only {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }
        
        .form-group input[type="text"]:disabled {
            background-color: #e9ecef;
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        .btn {
            background-color: #667eea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .btn:hover {
            background-color: #5568d3;
            transform: translateY(-1px);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn-primary {
            background-color: #667eea;
        }
        
        .btn-success {
            background-color: #4CAF50;
        }
        
        .btn-success:hover {
            background-color: #45a049;
        }
        
        .btn-warning {
            background-color: #ff9800;
        }
        
        .btn-warning:hover {
            background-color: #e68900;
        }
        
        .btn-danger {
            background-color: #f44336;
        }
        
        .btn-danger:hover {
            background-color: #da190b;
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn:disabled:hover {
            background-color: #ccc;
            transform: none;
        }
        
        .btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.2s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 0;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            animation: slideDown 0.3s;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 25px;
            border-radius: 8px 8px 0 0;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 20px;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .modal-footer {
            padding: 15px 25px;
            border-top: 1px solid #eee;
            text-align: right;
        }
        
        .close {
            color: white;
            float: right;
            font-size: 28px;
            font-weight: bold;
            line-height: 20px;
            cursor: pointer;
            opacity: 0.8;
        }
        
        .close:hover,
        .close:focus {
            opacity: 1;
        }
        
        .info-display {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }
        
        .info-display p {
            margin: 8px 0;
            font-size: 14px;
        }
        
        .info-display strong {
            color: #333;
            display: inline-block;
            min-width: 120px;
        }
        
        .device-list {
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 15px;
        }
        
        .device-item {
            padding: 12px;
            margin-bottom: 10px;
            border: 2px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .device-item:hover {
            background-color: #f8f9fa;
            border-color: #667eea;
        }
        
        .device-item input[type="radio"] {
            margin-right: 10px;
        }
        
        .device-item label {
            cursor: pointer;
            font-weight: 500;
            display: block;
        }
        
        .device-item.selected {
            background-color: #e7e9fd;
            border-color: #667eea;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            border-left: 4px solid #f44336;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            border-left: 4px solid #4CAF50;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .page-title {
                margin: -10px -10px 20px -10px;
                padding: 20px;
            }
            
            .section {
                padding: 15px;
            }
            
            .btn {
                width: 100%;
                margin-right: 0;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .device-info {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .device-info-text {
                flex: 1 1 100%;
                margin-bottom: 10px;
            }
            
            .device-info .btn {
                width: auto;
            }
        }
    </style>
</head>
<body>
    <?php echo $authHeader; ?>
    
    <div class="container">
        <div class="page-title">
            <h1>🎫 RFID Badger</h1>
            <p class="device-info">
                <span class="device-info-text">
                    Selected Device: <strong id="selectedDeviceName">None - Click Setup to configure</strong>
                </span>
                <button class="btn btn-secondary" onclick="pingDevice()" id="refreshDeviceBtn" disabled>Refresh</button>
            </p>
        </div>
        
        <!-- Member Information Section -->
        <div class="section">
            <h2>1. Request Member Information</h2>
            <div class="form-group">
                <label for="personNumberInput">Person Number:</label>
                <input type="text" id="personNumberInput" placeholder="Enter person number">
            </div>
            <button class="btn btn-primary" onclick="requestMemberInfo()" id="requestMemberInfoBtn">Request Member Info</button>
        </div>
        
        <!-- Burn Card Section -->
        <div class="section">
            <h2>2. Burn Card</h2>
            <p style="margin-bottom: 15px; color: #666;">
                Create a badge for the confirmed member. Person number must be confirmed first.
            </p>
            <div class="form-group">
                <label for="confirmedPersonNumber">Confirmed Person Number:</label>
                <input type="text" id="confirmedPersonNumber" readonly placeholder="Will populate after member info request">
                <span id="confirmedMemberName" style="margin-left: 15px; color: #667eea; font-weight: 600;"></span>
            </div>
            <button class="btn btn-success" onclick="burnCard()" id="burnCardBtn" disabled>Burn Card</button>
        </div>
        
        <!-- Setup Section -->
        <div class="section">
            <h2>3. Setup</h2>
            <p style="margin-bottom: 15px; color: #666;">
                Configure the badge reader device to use for all operations.
            </p>
            <button class="btn btn-secondary" onclick="showSetupModal()">Setup Device</button>
        </div>
    </div>
    
    <!-- Member Info Modal -->
    <div id="memberInfoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeMemberInfoModal()">&times;</span>
                <h2>Member Information</h2>
            </div>
            <div class="modal-body">
                <div class="info-display" id="memberInfoDisplay">
                    <!-- Will be populated with member info -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="confirmMember()">Confirm</button>
                <button class="btn btn-secondary" onclick="closeMemberInfoModal()">Cancel</button>
            </div>
        </div>
    </div>
    
    <!-- Device Setup Modal -->
    <div id="setupModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeSetupModal()">&times;</span>
                <h2>Select Badge Reader Device</h2>
            </div>
            <div class="modal-body">
                <div id="particleLoginSection">
                    <div style="padding: 20px;">
                        <p style="text-align: center; margin-bottom: 20px;">Connect to your Particle.io account</p>
                        
                        <!-- Custom credentials option -->
                        <div style="margin-bottom: 20px;">
                            <label style="display: flex; align-items: center; cursor: pointer;">
                                <input type="checkbox" id="useCustomCredentials" onchange="toggleCustomCredentials()" style="margin-right: 8px;">
                                Use custom credentials (optional)
                            </label>
                        </div>
                        
                        <div id="customCredentialsForm" style="display: none; margin-bottom: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 4px;">
                            <div style="margin-bottom: 12px;">
                                <label for="particleUsername" style="display: block; margin-bottom: 5px; font-weight: 600;">Username:</label>
                                <input type="text" id="particleUsername" style="width: 100%; padding: 8px; border: 2px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                            </div>
                            <div style="margin-bottom: 12px;">
                                <label for="particlePassword" style="display: block; margin-bottom: 5px; font-weight: 600;">Password:</label>
                                <input type="password" id="particlePassword" style="width: 100%; padding: 8px; border: 2px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                            </div>
                            <div style="font-size: 12px; color: #666; margin-top: 8px;">
                                <strong>Note:</strong> If not provided, server-stored credentials will be used.
                            </div>
                        </div>
                        
                        <div style="text-align: center;">
                            <button class="btn btn-primary" onclick="loginToParticle()" id="particleLoginBtn">Connect to Particle.io</button>
                        </div>
                    </div>
                    <div id="particleLoginStatus" style="margin-top: 10px;"></div>
                </div>
                <div id="deviceListContainer" style="display: none;">
                    <div class="loading">Loading devices...</div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="selectDevice()" id="selectDeviceBtn" disabled>Select</button>
                <button class="btn btn-secondary" onclick="closeSetupModal()">Cancel</button>
            </div>
        </div>
    </div>
    
    <script>
        // Global variables
        let selectedDeviceId = null;
        let selectedDeviceName = null;
        let currentMemberInfo = null;
        let availableDevices = [];
        let tempSelectedDevice = null;
        let particleAccessToken = null;
        
        // Particle.io API endpoints
        const PARTICLE_LOGIN_URL = 'https://api.particle.io/oauth/token';
        const PARTICLE_DEVICES_URL = 'https://api.particle.io/v1/devices';
        const PARTICLE_CLIENT_ID = 'particle';
        const PARTICLE_CLIENT_SECRET = 'particle';
        
        // Request Member Information
        async function requestMemberInfo() {
            const personNumber = document.getElementById('personNumberInput').value.trim();
            
            if (!personNumber) {
                alert('Please enter a person number.');
                return;
            }
            
            if (!selectedDeviceId) {
                alert('Please configure a device first using Setup.');
                return;
            }
            
            if (!particleAccessToken) {
                alert('Please login to Particle.io first using Setup.');
                return;
            }
            
            // Disable button during operation
            const requestBtn = document.getElementById('requestMemberInfoBtn');
            requestBtn.disabled = true;
            
            try {
                // Step 1: Call queryMember to initiate the request with person number
                // returns:
                //  0 = query accepted
                //	1 = query is underway
                //	2 = query is done and results are in the cloud variable (including any error report)
                //	3 = query was already underway, this query is rejected
                //	4 = memberNumber was not a number or was 0
                //	5 = other error, see LCD panel on device
                const url = `${PARTICLE_DEVICES_URL}/${selectedDeviceId}/queryMember`;
                const initialResponse = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${particleAccessToken}`
                    },
                    body: JSON.stringify({
                        arg: personNumber
                    })
                });
                
                if (!initialResponse.ok) {
                    throw new Error(`HTTP error! status: ${initialResponse.status}`);
                }
                
                const initialData = await initialResponse.json();
                console.log('queryMember initial response:', initialData);
                
                let returnValue = initialData.return_value;
                console.log('Initial return_value:', returnValue, 'type:', typeof returnValue);
                
                // Check for immediate errors (3, 4, or 5)
                if (returnValue == 3) {
                    throw new Error('Query already underway, this query is rejected (code 3)');
                } else if (returnValue == 4) {
                    throw new Error('Member number was not a number or was 0 (code 4)');
                } else if (returnValue == 5) {
                    throw new Error('Error occurred, see LCD panel on device (code 5)');
                }
                
                // Step 2: If return is 0 or 1, poll until we get 2 or higher
                if (returnValue == 0 || returnValue == 1) {
                    console.log(`Polling queryMember (starting with return_value = ${returnValue})...`);
                    const maxAttempts = 10; // 20 seconds maximum
                    let attempts = 0;
                    
                    while (returnValue < 2  && attempts < maxAttempts) {
                        // Wait 2 seconds before next poll
                        await new Promise(resolve => setTimeout(resolve, 2000));
                        attempts++;
                        
                        // Poll queryMember without parameter
                        const pollResponse = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Authorization': `Bearer ${particleAccessToken}`
                            },
                            body: JSON.stringify({
                                arg: personNumber
                            })
                        });
                        
                        if (!pollResponse.ok) {
                            throw new Error(`HTTP error during polling! status: ${pollResponse.status}`);
                        }
                        
                        const pollData = await pollResponse.json();
                        returnValue = pollData.return_value;
                        console.log(`Poll attempt ${attempts}: return_value = ${returnValue}`);
                        
                        // Check for errors during polling
                        //if (returnValue == 3) {
                        //    throw new Error('Query already underway, this query is rejected (code 3)');
                        //}  else 
                        if (returnValue == 4) {
                            throw new Error('Member number was not a number or was 0 (code 4)');
                        } else if (returnValue == 5) {
                            throw new Error('Error occurred, see LCD panel on device (code 5)');
                        }
                    }
                    
                    // Check if we timed out
                    if (returnValue < 2) {
                        throw new Error(`Timeout waiting for query to complete after ${maxAttempts} seconds`);
                    }
                }
                
                // Step 3: If return is 2, fetch the result
                if (returnValue != 2) {
                    throw new Error(`Unexpected return value: ${returnValue}`);
                }
                
                console.log('Query complete (return_value = 2). Fetching queryMemberResult...');
                
                // queryMemberResult is a device variable, not a function, so use GET
                const resultUrl = `${PARTICLE_DEVICES_URL}/${selectedDeviceId}/queryMemberResult?access_token=${particleAccessToken}`;
                const resultResponse = await fetch(resultUrl, {
                    method: 'GET'
                });
                
                if (!resultResponse.ok) {
                    throw new Error(`HTTP error on queryMemberResult! status: ${resultResponse.status}`);
                }
                
                const resultData = await resultResponse.json();
                console.log('queryMemberResult response:', resultData);
                
                // Parse the result from Particle variable (it's in the 'result' field, not 'return_value')
                let memberData;
                if (resultData.result) {
                    // The result field contains a JSON string, parse it
                    try {
                        memberData = typeof resultData.result === 'string' 
                            ? JSON.parse(resultData.result) 
                            : resultData.result;
                    } catch (e) {
                        console.error('Failed to parse queryMemberResult result:', e);
                        alert(`Error parsing member data: ${e.message}\n\nRaw result: ${resultData.result}`);
                        return;
                    }
                } else {
                    console.error('No result field in response');
                    alert(`Error: No result field in response\n\nFull response:\n${JSON.stringify(resultData, null, 2)}`);
                    return;
                }
                console.log('Parsed member data:', memberData);
                
                // Extract the required fields: Name, ClientID, Status, Checkin
                currentMemberInfo = {
                    name: memberData.Name || memberData.name || 'Unknown',
                    clientId: memberData.ClientID || memberData.clientId || memberData.PersonNumber || personNumber,
                    status: memberData.Status || memberData.status || 'Unknown',
                    checkin: memberData.Checkin || memberData.checkin || 'Unknown'
                };
                console.log('Extracted member info:', currentMemberInfo);
                
                // Display in modal
                showMemberInfoModal(currentMemberInfo);
                
            } catch (error) {
                alert('Error requesting member information: ' + error.message);
                console.error('Error:', error);
            } finally {
                // Re-enable button after operation completes
                requestBtn.disabled = false;
            }
        }
        
        function showMemberInfoModal(info) {
            const modal = document.getElementById('memberInfoModal');
            const display = document.getElementById('memberInfoDisplay');
            
            display.innerHTML = `
                <p><strong>Name:</strong> ${info.name}</p>
                <p><strong>Client ID:</strong> ${info.clientId}</p>
                <p><strong>Status:</strong> ${info.status}</p>
                <p><strong>Checkin:</strong> ${info.checkin}</p>
            `;
            
            modal.style.display = 'block';
        }
        
        function closeMemberInfoModal() {
            document.getElementById('memberInfoModal').style.display = 'none';
        }
        
        function confirmMember() {
            if (currentMemberInfo) {
                document.getElementById('confirmedPersonNumber').value = currentMemberInfo.clientId;
                document.getElementById('confirmedMemberName').textContent = currentMemberInfo.name;
                document.getElementById('burnCardBtn').disabled = false;
                closeMemberInfoModal();
            }
        }
        
        // Burn Card
        async function burnCard() {
            const personNumber = document.getElementById('confirmedPersonNumber').value.trim();
            
            if (!personNumber) {
                alert('Please request and confirm member information first.');
                return;
            }
            
            if (!selectedDeviceId) {
                alert('Please configure a device first using Setup.');
                return;
            }
            
            if (!particleAccessToken) {
                alert('Please login to Particle.io first using Setup.');
                return;
            }
            
            try {
                const url = `${PARTICLE_DEVICES_URL}/${selectedDeviceId}/burnCard`;
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${particleAccessToken}`
                    },
                    body: JSON.stringify({
                        arg: personNumber
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                // returns:
                //  	0 = card burned successfully
                //	    1 = error contacting the hardware/bad card; burn unsuccessful
                //	    2 = clientID does not match clientID from last queryMember call
                //      3 = data is not a number or is 0
                const result = await response.json();
                console.log('burnCard response:', result);
                
                const returnValue = result.return_value;
                console.log('burnCard return_value:', returnValue);
                
                // Handle different return codes
                if (returnValue == 0) {
                    alert('✅ Follow instructions on LCD.');
                } else if (returnValue == 1) {
                    alert('❌ Error burning card!\n\nError contacting the hardware or bad card. Burn unsuccessful.\n\nPlease check the device and try again with a new card.');
                } else if (returnValue == 2) {
                    alert('❌ Error burning card!\n\nClient ID does not match the last queryMember call.\n\nPlease request member information again before burning a card.');
                    // Clear confirmed member info
                    document.getElementById('confirmedPersonNumber').value = '';
                    document.getElementById('confirmedMemberName').textContent = '';
                    document.getElementById('burnCardBtn').disabled = true;
                } else if (returnValue == 3) {
                    alert('❌ Error burning card!\n\nData is not a number or is 0.\n\nPlease verify the person number and try again.');
                } else {
                    alert('⚠️ Unexpected response from device!\n\nReturn code: ' + returnValue + '\n\nFull response:\n' + JSON.stringify(result, null, 2));
                }
                
            } catch (error) {
                alert('Error burning card: ' + error.message);
                console.error('Error:', error);
            }
        }
        
        // Toggle custom credentials form visibility
        function toggleCustomCredentials() {
            const checkbox = document.getElementById('useCustomCredentials');
            const form = document.getElementById('customCredentialsForm');
            form.style.display = checkbox.checked ? 'block' : 'none';
        }
        
        // Login to Particle.io (server-side authentication)
        async function loginToParticle() {
            const statusDiv = document.getElementById('particleLoginStatus');
            const loginBtn = document.getElementById('particleLoginBtn');
            
            loginBtn.disabled = true;
            statusDiv.innerHTML = '<div class="loading">Authenticating with Particle.io...</div>';
            
            try {
                // Prepare request body
                const requestBody = {};
                
                // Check if custom credentials are provided
                const useCustom = document.getElementById('useCustomCredentials').checked;
                if (useCustom) {
                    const username = document.getElementById('particleUsername').value.trim();
                    const password = document.getElementById('particlePassword').value.trim();
                    
                    if (!username || !password) {
                        throw new Error('Please enter both username and password for custom credentials');
                    }
                    
                    requestBody.username = username;
                    requestBody.password = password;
                }
                
                // Call server-side endpoint
                const response = await fetch('particle_login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(requestBody)
                });
                
                const contentType = response.headers.get('content-type');
                let data;
                
                if (contentType && contentType.includes('application/json')) {
                    data = await response.json();
                } else {
                    // Response is not JSON, likely an error page
                    const text = await response.text();
                    console.error('Non-JSON response:', text);
                    throw new Error(`Server returned non-JSON response (status ${response.status}). Check console for details.`);
                }
                
                if (!response.ok) {
                    throw new Error(data.error || `Authentication failed: ${response.status}`);
                }
                
                particleAccessToken = data.access_token;
                
                // Save token to localStorage
                localStorage.setItem('particleAccessToken', particleAccessToken);
                
                statusDiv.innerHTML = '<div class="success">Login successful! Loading devices...</div>';
                
                // Hide login form and load devices
                document.getElementById('particleLoginSection').style.display = 'none';
                await loadParticleDevices();
                
            } catch (error) {
                statusDiv.innerHTML = `<div class="error">Login failed: ${error.message}</div>`;
                loginBtn.disabled = false;
                console.error('Error:', error);
            }
        }
        
        // Load devices from Particle.io
        async function loadParticleDevices() {
            const container = document.getElementById('deviceListContainer');
            container.style.display = 'block';
            container.innerHTML = '<div class="loading">Loading devices...</div>';
            
            try {
                // Fetch devices from Particle
                const response = await fetch(`${PARTICLE_DEVICES_URL}?access_token=${particleAccessToken}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                // Particle returns an array of devices
                availableDevices = Array.isArray(data) ? data : [];
                
                if (availableDevices.length === 0) {
                    container.innerHTML = '<div class="error">No devices found in your Particle account.</div>';
                    return;
                }
                
                // Fetch preferred device name from server
                let preferredDeviceName = null;
                try {
                    const prefResponse = await fetch('get_preferred_device.php');
                    if (prefResponse.ok) {
                        const prefData = await prefResponse.json();
                        preferredDeviceName = prefData.preferredDevice;
                        console.log('Preferred device name from config:', preferredDeviceName);
                    }
                } catch (error) {
                    console.warn('Could not fetch preferred device:', error);
                }
                
                // Check if preferred device exists in the list
                let preferredDeviceIndex = -1;
                if (preferredDeviceName) {
                    preferredDeviceIndex = availableDevices.findIndex(d => d.name === preferredDeviceName);
                    console.log('Preferred device index:', preferredDeviceIndex);
                }
                
                // If preferred device found, auto-select it
                if (preferredDeviceIndex >= 0) {
                    const device = availableDevices[preferredDeviceIndex];
                    tempSelectedDevice = {
                        id: device.id,
                        name: device.name || `Device ${device.id}`
                    };
                    document.getElementById('selectDeviceBtn').disabled = false;
                    
                    // Show success message with device list hidden
                    container.innerHTML = `
                        <div class="success">
                            ✅ Preferred device found and selected: <strong>${tempSelectedDevice.name}</strong>
                            <br><br>
                            <button class="btn btn-secondary" onclick="showDeviceList()" style="font-size: 12px; padding: 8px 16px;">Show All Devices</button>
                        </div>
                        <div id="deviceListDisplay" style="display: none;"></div>
                    `;
                } else {
                    // No preferred device or not found, show device list
                    if (preferredDeviceName) {
                        container.innerHTML = `
                            <div class="error" style="margin-bottom: 15px;">
                                Preferred device "${preferredDeviceName}" not found in your account.
                            </div>
                            <div id="deviceListDisplay"></div>
                        `;
                    } else {
                        container.innerHTML = '<div id="deviceListDisplay"></div>';
                    }
                    displayDeviceList(availableDevices);
                }
                
            } catch (error) {
                container.innerHTML = `<div class="error">Error loading devices: ${error.message}</div>`;
                console.error('Error:', error);
            }
        }
        
        // Show the device list (called when user clicks "Show All Devices" button)
        function showDeviceList() {
            const displayDiv = document.getElementById('deviceListDisplay');
            if (displayDiv) {
                displayDiv.style.display = 'block';
                displayDeviceList(availableDevices);
            }
        }
        
        // Setup Device Modal
        async function showSetupModal() {
            const modal = document.getElementById('setupModal');
            const loginSection = document.getElementById('particleLoginSection');
            const deviceListContainer = document.getElementById('deviceListContainer');
            const statusDiv = document.getElementById('particleLoginStatus');
            const loginBtn = document.getElementById('particleLoginBtn');
            
            modal.style.display = 'block';
            
            // Check if we have a saved token
            const savedToken = localStorage.getItem('particleAccessToken');
            
            if (savedToken) {
                particleAccessToken = savedToken;
                loginSection.style.display = 'none';
                deviceListContainer.style.display = 'block';
                await loadParticleDevices();
            } else {
                // Reset the form
                loginSection.style.display = 'block';
                deviceListContainer.style.display = 'none';
                statusDiv.innerHTML = '';
                loginBtn.disabled = false;
            }
        }
        
        function closeSetupModal() {
            document.getElementById('setupModal').style.display = 'none';
        }
        
        function displayDeviceList(devices) {
            const displayDiv = document.getElementById('deviceListDisplay');
            const targetContainer = displayDiv || document.getElementById('deviceListContainer');
            
            let html = '<div style="margin-bottom: 15px;"><button class="btn btn-secondary" onclick="showParticleLogin()" style="font-size: 12px; padding: 8px 16px;">Change Account</button></div>';
            html += '<div class="device-list">';
            
            devices.forEach((device, index) => {
                // Particle devices have 'id' and 'name' fields
                const deviceId = device.id;
                const deviceName = device.name || `Device ${deviceId}`;
                const connected = device.connected ? '🟢 Online' : '🔴 Offline';
                
                html += `
                    <div class="device-item" onclick="selectDeviceItem(${index})" id="device-item-${index}">
                        <input type="radio" name="device" id="device-${index}" value="${deviceId}">
                        <label for="device-${index}">
                            <strong>${deviceName}</strong> ${connected}<br>
                            <small style="color: #666;">ID: ${deviceId}</small>
                        </label>
                    </div>
                `;
            });
            
            html += '</div>';
            
            targetContainer.innerHTML = html;
        }
        
        function showParticleLogin() {
            // Clear the token when switching accounts
            particleAccessToken = null;
            localStorage.removeItem('particleAccessToken');
            
            document.getElementById('particleLoginSection').style.display = 'block';
            document.getElementById('deviceListContainer').style.display = 'none';
            document.getElementById('particleLoginStatus').innerHTML = '';
            document.getElementById('particleLoginBtn').disabled = false;
        }
        
        function selectDeviceItem(index) {
            // Remove previous selection styling
            document.querySelectorAll('.device-item').forEach(item => {
                item.classList.remove('selected');
            });
            
            // Add selection styling
            const item = document.getElementById(`device-item-${index}`);
            item.classList.add('selected');
            
            // Select the radio button
            const radio = document.getElementById(`device-${index}`);
            radio.checked = true;
            
            // Store temporarily selected device
            const device = availableDevices[index];
            tempSelectedDevice = {
                id: device.id,
                name: device.name || `Device ${device.id}`
            };
            
            // Enable select button
            document.getElementById('selectDeviceBtn').disabled = false;
        }
        
        async function selectDevice() {
            if (!tempSelectedDevice) {
                alert('Please select a device.');
                return;
            }
            
            try {
                // TODO: Update this URL if you need to call an API to set the device
                // const url = `https://your-api-server.com/api/set-device`;
                // const response = await fetch(url, {
                //     method: 'POST',
                //     headers: {
                //         'Content-Type': 'application/json',
                //     },
                //     body: JSON.stringify({ deviceId: tempSelectedDevice.id })
                // });
                
                // For now, just set it locally
                selectedDeviceId = tempSelectedDevice.id;
                selectedDeviceName = tempSelectedDevice.name;
                
                // Save to localStorage
                localStorage.setItem('badgerDeviceId', selectedDeviceId);
                localStorage.setItem('badgerDeviceName', selectedDeviceName);
                
                // Update display
                document.getElementById('selectedDeviceName').textContent = selectedDeviceName;
                
                // Enable refresh button
                document.getElementById('refreshDeviceBtn').disabled = false;
                
                // Close modal
                closeSetupModal();
                
                alert(`Device selected: ${selectedDeviceName}`);
                
                // Ping the device to check its status
                pingDevice();
                
            } catch (error) {
                alert('Error selecting device: ' + error.message);
                console.error('Error:', error);
            }
        }
        
        // Enable or disable features based on device status
        function setFeaturesEnabled(enabled) {
            document.getElementById('personNumberInput').disabled = !enabled;
            document.getElementById('requestMemberInfoBtn').disabled = !enabled;
            document.getElementById('confirmedPersonNumber').disabled = !enabled;
            
            // burnCardBtn may already be disabled if no member is confirmed
            // Only enable it if features are enabled AND a member is confirmed
            if (!enabled) {
                document.getElementById('burnCardBtn').disabled = true;
            }
            // If enabled, leave burnCardBtn state as is (it's controlled by member confirmation)
        }
        
        // Ping the selected device to check its status
        async function pingDevice() {
            if (!selectedDeviceId || !particleAccessToken) {
                setFeaturesEnabled(false);
                return;
            }
            
            const deviceInfoElement = document.querySelector('.device-info');
            
            // Set background to yellow while pinging
            deviceInfoElement.style.backgroundColor = '#ffeb3b';
            deviceInfoElement.style.color = '#856404';
            console.log('Pinging device:', selectedDeviceId);
            
            // Disable features while pinging
            setFeaturesEnabled(false);
            
            try {
                const url = `${PARTICLE_DEVICES_URL}/${selectedDeviceId}/ping?access_token=${particleAccessToken}`;
                const response = await fetch(url, {
                    method: 'PUT'
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Ping response:', data);
                
                // Check if Online is true and Ok is true
                if (data.online === true && data.ok === true) {
                    // Set background to light green
                    deviceInfoElement.style.backgroundColor = '#c8e6c9';
                    deviceInfoElement.style.color = '#2e7d32';
                    console.log('Device is online and OK');
                    // Enable features
                    setFeaturesEnabled(true);
                } else {
                    // Set background to red
                    deviceInfoElement.style.backgroundColor = '#ffcdd2';
                    deviceInfoElement.style.color = '#c62828';
                    console.log('Device is offline or not OK');
                    // Keep features disabled
                    setFeaturesEnabled(false);
                }
                
            } catch (error) {
                // Set background to red on error
                const deviceInfoElement = document.querySelector('.device-info');
                deviceInfoElement.style.backgroundColor = '#ffcdd2';
                deviceInfoElement.style.color = '#c62828';
                console.error('Error pinging device:', error);
                // Keep features disabled
                setFeaturesEnabled(false);
            }
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const memberModal = document.getElementById('memberInfoModal');
            const setupModal = document.getElementById('setupModal');
            
            if (event.target == memberModal) {
                closeMemberInfoModal();
            }
            if (event.target == setupModal) {
                closeSetupModal();
            }
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            console.log('RFID Badger initialized');
            
            // Disable features by default until device is verified online
            setFeaturesEnabled(false);
            
            // Clear confirmed member info when person number input changes
            document.getElementById('personNumberInput').addEventListener('input', function() {
                const confirmedPersonNumber = document.getElementById('confirmedPersonNumber').value;
                const currentInput = this.value.trim();
                
                // If user enters a different person number, clear the confirmed member display
                if (confirmedPersonNumber && currentInput !== confirmedPersonNumber) {
                    document.getElementById('confirmedPersonNumber').value = '';
                    document.getElementById('confirmedMemberName').textContent = '';
                    document.getElementById('burnCardBtn').disabled = true;
                }
            });
            
            // Load saved Particle access token from localStorage
            const savedToken = localStorage.getItem('particleAccessToken');
            if (savedToken) {
                particleAccessToken = savedToken;
                console.log('Particle access token loaded from localStorage');
            }
            
            // Load saved device from localStorage if available
            const savedDeviceId = localStorage.getItem('badgerDeviceId');
            const savedDeviceName = localStorage.getItem('badgerDeviceName');
            
            if (savedDeviceId && savedDeviceName) {
                selectedDeviceId = savedDeviceId;
                selectedDeviceName = savedDeviceName;
                document.getElementById('selectedDeviceName').textContent = savedDeviceName;
                
                // Enable refresh button
                document.getElementById('refreshDeviceBtn').disabled = false;
                
                // Ping the device to check its status (will enable features if online)
                pingDevice();
            } else {
                // No device selected, features remain disabled
                console.log('No device selected');
            }
        });
    </script>
</body>
</html>
