<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Face Verification</title>
  <style>
    body {
      font-family: 'Arial', sans-serif;
      background: #f4f6f9;
      margin: 0;
      padding: 0;
      text-align: center;
      color: #333;
    }

    h2 {
      margin-top: 20px;
      font-size: 24px;
    }

    .container {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 20px;
    }

    video, canvas {
      width: 100%;
      max-width: 350px;
      border-radius: 12px;
      background: #000;
      margin: 15px 0;
      position: relative;
    }

    #faceStatus {
      font-size: 16px;
      margin-bottom: 10px;
      font-weight: bold;
    }

    .face-ok {
      color: #4caf50;
    }

    .face-missing {
      color: #f44336;
    }

    .progress-container {
      width: 80%;
      max-width: 350px;
      background: #ddd;
      height: 8px;
      border-radius: 4px;
      overflow: hidden;
      margin: 10px auto;
    }

    .progress-bar {
      height: 8px;
      width: 0%;
      background: #4caf50;
      transition: width 0.3s ease-in-out;
    }

    .steps {
      display: flex;
      justify-content: space-around;
      margin: 10px auto;
      width: 90%;
      max-width: 400px;
    }

    .step {
      display: flex;
      flex-direction: column;
      align-items: center;
      font-size: 14px;
      color: #888;
      transition: transform 0.3s;
    }

    .step-icon {
      font-size: 24px;
      margin-bottom: 5px;
    }

    .step.active {
      color: #2196f3;
      font-weight: bold;
      animation: pulse 1s infinite;
    }

    .step.completed {
      color: #4caf50;
    }

    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.1); }
      100% { transform: scale(1); }
    }

    #instruction {
      font-size: 18px;
      margin: 15px;
      font-weight: bold;
    }

    .hidden {
      display: none;
    }

    #actions button {
      padding: 10px 20px;
      margin: 10px;
      font-size: 16px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
    }

    #use-photo {
      background: #4caf50;
      color: white;
    }

    #retake-photo {
      background: #f44336;
      color: white;
    }

    /* Countdown styling */
    #countdown {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-size: 60px;
      font-weight: bold;
      color: white;
      text-shadow: 2px 2px 8px rgba(0,0,0,0.7);
      display: none;
    }

    /* Face box */
    #faceBox {
      position: absolute;
      border: 2px solid limegreen;
      display: none;
      pointer-events: none;
    }

    .video-container {
      position: relative;
      display: inline-block;
    }
  </style>
</head>
<body>
  <h2>Face Verification</h2>

  <div class="container">
    <!-- Face Detection Status -->
    <p id="faceStatus" class="face-missing">🔴 No face detected</p>

    <!-- Steps Indicators -->
    <div class="steps">
      <div class="step active" id="step1">
        <div class="step-icon">👈</div>
        Left
      </div>
      <div class="step" id="step2">
        <div class="step-icon">👉</div>
        Right
      </div>
      <div class="step" id="step3">
        <div class="step-icon">😊</div>
        Smile
      </div>
    </div>

    <!-- Progress bar -->
    <div class="progress-container">
      <div class="progress-bar" id="progressBar"></div>
    </div>

    <p id="instruction">Initializing camera...</p>

    <!-- Video + Face Box Overlay -->
    <div class="video-container">
      <video id="video" autoplay playsinline></video>
      <div id="faceBox"></div>
      <div id="countdown"></div>
    </div>

    <canvas id="snapshot" class="hidden"></canvas>

    <div id="actions" class="hidden">
      <button id="use-photo">✅ Use this selfie</button>
      <button id="retake-photo">🔄 Retake</button>
    </div>
  </div>

  <!-- TensorFlow.js & Face Landmarks -->
  <!-- TensorFlow core + backend -->
  <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs-core"></script>
  <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs-converter"></script>
  <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs-backend-webgl"></script>
  
  <!-- Face landmarks detection -->
  <script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/face-landmarks-detection"></script>
  
  <!-- ✅ Mediapipe runtime -->
  <script src="https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh"></script>


  <script>
    const video = document.getElementById('video');
    const instruction = document.getElementById('instruction');
    const faceStatus = document.getElementById('faceStatus');
    const canvas = document.getElementById('snapshot');
    const ctx = canvas.getContext('2d');
    const progressBar = document.getElementById('progressBar');
    const faceBox = document.getElementById('faceBox');
    const countdownEl = document.getElementById('countdown');

    const stepElements = [
      document.getElementById('step1'),
      document.getElementById('step2'),
      document.getElementById('step3')
    ];

    let model;
    let currentStep = 0;
    let stepCompleted = [false, false, false];
    let faceDetected = false;

    async function initCamera() {
      try {
        const stream = await navigator.mediaDevices.getUserMedia({
          video: { facingMode: 'user' },
          audio: false
        });
        video.srcObject = stream;
      } catch (err) {
        instruction.textContent = "❌ Camera access denied. Please enable permissions.";
      }
    }
    
    async function loadModel() {
    const modelType = faceLandmarksDetection.SupportedModels.MediaPipeFaceMesh;
    
    model = await faceLandmarksDetection.createDetector(modelType, {
    runtime: 'mediapipe',
    refineLandmarks: true,
    solutionPath: 'https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh'
    });
    
    console.log("✅ Model Loaded");
    detectFace();
    }
    
    async function detectFace() {
    const faces = await model.estimateFaces(video);
    
    if (faces.length > 0) {
    console.log("Face detected ✅");
    } else {
    console.log("No face ❌");
    }
    
    requestAnimationFrame(detectFace);
    }
    
    async function detectSteps() {
    const predictions = await model.estimateFaces(video);
    
    if (predictions.length > 0) {
    faceDetected = true;
    faceStatus.textContent = "✅ Face Detected";
    faceStatus.classList.remove('face-missing');
    faceStatus.classList.add('face-ok');
    
    const face = predictions[0].boundingBox;
    const keypoints = predictions[0].scaledMesh;
    
    // Show face box
    faceBox.style.display = "block";
    faceBox.style.left = `${face.topLeft[0][0]}px`;
    faceBox.style.top = `${face.topLeft[0][1]}px`;
    faceBox.style.width = `${face.bottomRight[0][0] - face.topLeft[0][0]}px`;
    faceBox.style.height = `${face.bottomRight[0][1] - face.topLeft[0][1]}px`;
    
    const nose = keypoints[1];
    const leftCheek = keypoints[234];
    const rightCheek = keypoints[454];
    const mouthLeft = keypoints[78];
    const mouthRight = keypoints[308];
    const upperLip = keypoints[13];
    const lowerLip = keypoints[14];
    
    const dxLeft = Math.abs(nose[0] - leftCheek[0]);
    const dxRight = Math.abs(nose[0] - rightCheek[0]);
    const mouthWidth = Math.abs(mouthRight[0] - mouthLeft[0]);
    const mouthHeight = Math.abs(upperLip[1] - lowerLip[1]);
    const smileRatio = mouthWidth / mouthHeight;
    
    if (currentStep === 0 && dxLeft < dxRight - 20) {
    stepCompleted[0] = true;
    currentStep++;
    instruction.textContent = "✅ Left detected! Now turn RIGHT";
    updateProgressUI();
    }
    
    if (currentStep === 1 && dxRight < dxLeft - 20) {
    stepCompleted[1] = true;
    currentStep++;
    instruction.textContent = "✅ Right detected! Now SMILE 😊";
    updateProgressUI();
    }
    
    if (currentStep === 2 && smileRatio > 2.5) {
    stepCompleted[2] = true;
    currentStep++;
    instruction.textContent = "✅ Smile detected!";
    updateProgressUI();
    
    // Show countdown before capture
    startCountdown(() => capturePhoto());
    }
    } else {
    faceDetected = false;
    faceStatus.textContent = "🔴 No face detected";
    faceStatus.classList.remove('face-ok');
    faceStatus.classList.add('face-missing');
    faceBox.style.display = "none";
    }
    
    if (currentStep < stepElements.length) {
    requestAnimationFrame(detectSteps);
    }
    }

    function updateProgressUI() {
      // Mark completed steps
      stepElements.forEach((el, idx) => {
        el.classList.remove('active', 'completed');
        if (idx < currentStep) {
          el.classList.add('completed');
        } else if (idx === currentStep) {
          el.classList.add('active');
        }
      });

      const progressPercent = (currentStep / stepElements.length) * 100;
      progressBar.style.width = `${progressPercent}%`;
    }

    

    function startCountdown(callback) {
      let counter = 3;
      countdownEl.style.display = 'block';
      countdownEl.textContent = counter;

      const interval = setInterval(() => {
        counter--;
        if (counter > 0) {
          countdownEl.textContent = counter;
        } else {
          clearInterval(interval);
          countdownEl.style.display = 'none';
          callback();
        }
      }, 1000);
    }

    function capturePhoto() {
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

      canvas.classList.remove("hidden");
      document.getElementById("actions").classList.remove("hidden");
      video.classList.add("hidden");
      instruction.textContent = "Do you want to use this selfie?";
      progressBar.style.width = "100%";
    }

    document.getElementById("retake-photo").addEventListener("click", () => {
      currentStep = 0;
      stepCompleted = [false, false, false];
      video.classList.remove("hidden");
      canvas.classList.add("hidden");
      document.getElementById("actions").classList.add("hidden");
      instruction.textContent = "Turn your head LEFT";
      updateProgressUI();
      detectSteps();
    });

    document.getElementById("use-photo").addEventListener("click", () => {
      const dataURL = canvas.toDataURL("image/png");
      alert("✅ Selfie captured and ready to upload!");
      // Send `dataURL` to backend if needed
    });

    // Init camera & model
    initCamera().then(loadModel);
    updateProgressUI();
  </script>
</body>
</html>
</body>
</html>