<!DOCTYPE html>
<html>
<head>
<title></title>
</head>
<body>
<video id="video" autoplay playsinline></video>
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs-core"></script>
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs-converter"></script>
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs-backend-webgl"></script>
<script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/face-landmarks-detection"></script>
<script>
  let video = document.getElementById("video");
  let model;

  async function initCamera() {
    let stream = await navigator.mediaDevices.getUserMedia({ video: true });
    video.srcObject = stream;
    return new Promise(resolve => video.onloadedmetadata = resolve);
  }

  async function loadModel() {
    const modelType = faceLandmarksDetection.SupportedModels.MediaPipeFaceMesh;

    model = await faceLandmarksDetection.createDetector(modelType, {
      runtime: 'tfjs',
      refineLandmarks: true
    });

    console.log("✅ Model Loaded");
    detectFace();
  }

  async function detectFace() {
    const faces = await model.estimateFaces(video);
    console.log("Faces:", faces.length);
    requestAnimationFrame(detectFace);
  }
  
  loadModel();

  /*initCamera().then(loadModel);*/
</script>
</body>
</html>