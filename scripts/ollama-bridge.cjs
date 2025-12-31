const http = require('http');

// Read input from stdin
let inputData = '';
process.stdin.on('data', chunk => {
    inputData += chunk;
});

process.stdin.on('end', () => {
    if (!inputData) {
        // console.error("No input data provided");
        // process.exit(1);
        return;
    }

    try {
        const payload = JSON.parse(inputData);
        sendToOllama(payload);
    } catch (e) {
        console.error("Invalid JSON input: " + e.message);
        process.exit(1);
    }
});

function sendToOllama(payload) {
    const options = {
        hostname: 'localhost',
        port: 11434,
        path: '/api/generate',
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Content-Length': Buffer.byteLength(JSON.stringify(payload))
        },
        timeout: 300000 // 5 minutes
    };

    const req = http.request(options, res => {
        let output = '';
        res.on('data', chunk => output += chunk);
        res.on('end', () => {
            try {
                // Ollama returns a JSON object with 'response' field
                // Ensure we output ONLY the JSON, nothing else
                const json = JSON.parse(output);
                process.stdout.write(JSON.stringify(json));
            } catch (e) {
                // Try to handle if it's multiple JSON objects (streaming mode accident)
                console.error("Failed to parse Ollama response: " + e.message + "\nRaw: " + output.substring(0, 100));
                process.exit(1);
            }
        });
    });

    req.on('error', error => {
        console.error("Request error: " + error.message);
        process.exit(1);
    });

    req.write(JSON.stringify(payload));
    req.end();
}
