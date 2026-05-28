"""
Flask API Server for Citrus Leaf Disease Detection
Uses YOLOv8 (ultralytics) if available and model exists, otherwise runs in MOCK mode.
"""

import os
import uuid
import random
from datetime import datetime

from flask import Flask, request, jsonify, send_from_directory
from flask_cors import CORS

# Try to import ultralytics for YOLOv8
try:
    from ultralytics import YOLO
    ULTRALYTICS_AVAILABLE = True
except ImportError:
    ULTRALYTICS_AVAILABLE = False

from PIL import Image, ImageDraw, ImageFont

# ---------------------------------------------------------------------------
# Configuration
# ---------------------------------------------------------------------------

app = Flask(__name__)
CORS(app)

app.config['MAX_CONTENT_LENGTH'] = 5 * 1024 * 1024  # 5MB max upload

ALLOWED_EXTENSIONS = {'jpg', 'jpeg', 'png'}
CLASSES = ["Canker", "HLB", "Greasy Spot", "Melanose", "Sooty Mold", "Healthy"]
MODEL_PATH = os.path.join(os.path.dirname(__file__), 'model', 'best.pt')
RESULTS_DIR = os.path.join(os.path.dirname(__file__), 'static', 'results')

# Ensure results directory exists
os.makedirs(RESULTS_DIR, exist_ok=True)

# Determine running mode
USE_YOLO = ULTRALYTICS_AVAILABLE and os.path.isfile(MODEL_PATH)
model = None

if USE_YOLO:
    model = YOLO(MODEL_PATH)
    print("[INFO] Running in YOLO mode with model:", MODEL_PATH)
else:
    print("[INFO] Running in MOCK mode (ultralytics not installed or model/best.pt not found)")


# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

def allowed_file(filename):
    """Check if file extension is allowed."""
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS


def mock_predict(image_path):
    """
    Mock prediction: opens the image, draws a random bounding box with a
    random class label, and returns prediction data.
    """
    img = Image.open(image_path).convert("RGB")
    draw = ImageDraw.Draw(img)

    w, h = img.size

    # Generate random bounding box
    x1 = random.randint(int(w * 0.1), int(w * 0.4))
    y1 = random.randint(int(h * 0.1), int(h * 0.4))
    x2 = random.randint(int(w * 0.6), int(w * 0.9))
    y2 = random.randint(int(h * 0.6), int(h * 0.9))

    # Pick random class and confidence
    class_name = random.choice(CLASSES)
    confidence = round(random.uniform(0.70, 0.99), 2)

    # Draw bounding box
    box_color = (0, 255, 0) if class_name == "Healthy" else (255, 0, 0)
    draw.rectangle([x1, y1, x2, y2], outline=box_color, width=3)

    # Draw label background
    label = f"{class_name} {confidence:.2f}"
    try:
        font = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf", 16)
    except (IOError, OSError):
        font = ImageFont.load_default()

    # Get text bounding box
    text_bbox = draw.textbbox((x1, y1), label, font=font)
    text_w = text_bbox[2] - text_bbox[0]
    text_h = text_bbox[3] - text_bbox[1]

    draw.rectangle([x1, y1 - text_h - 6, x1 + text_w + 6, y1], fill=box_color)
    draw.text((x1 + 3, y1 - text_h - 4), label, fill=(255, 255, 255), font=font)

    # Save result image
    result_filename = f"result_{uuid.uuid4().hex[:8]}.jpg"
    result_path = os.path.join(RESULTS_DIR, result_filename)
    img.save(result_path, "JPEG", quality=90)

    return {
        "nama_penyakit": class_name,
        "confidence": confidence,
        "result_image": result_filename
    }


def yolo_predict(image_path):
    """
    Real YOLOv8 prediction using the trained model.
    """
    results = model.predict(source=image_path, conf=0.25, save=False)
    result = results[0]

    # Get the annotated image
    img_array = result.plot()
    img = Image.fromarray(img_array[..., ::-1])  # BGR to RGB

    # Extract best detection
    if len(result.boxes) > 0:
        # Get the detection with highest confidence
        confidences = result.boxes.conf.cpu().numpy()
        best_idx = confidences.argmax()
        best_conf = float(confidences[best_idx])
        best_cls = int(result.boxes.cls.cpu().numpy()[best_idx])

        if best_cls < len(CLASSES):
            class_name = CLASSES[best_cls]
        else:
            class_name = f"Class_{best_cls}"

        confidence = round(best_conf, 2)
    else:
        # No detections
        class_name = "Healthy"
        confidence = 0.50

    # Save result image
    result_filename = f"result_{uuid.uuid4().hex[:8]}.jpg"
    result_path = os.path.join(RESULTS_DIR, result_filename)
    img.save(result_path, "JPEG", quality=90)

    return {
        "nama_penyakit": class_name,
        "confidence": confidence,
        "result_image": result_filename
    }


# ---------------------------------------------------------------------------
# Routes
# ---------------------------------------------------------------------------

@app.route('/', methods=['GET'])
def index():
    """API info endpoint."""
    return jsonify({
        "name": "Citrus Leaf Disease Detection API",
        "version": "1.0.0",
        "mode": "YOLO" if USE_YOLO else "MOCK",
        "classes": CLASSES,
        "endpoints": {
            "GET /": "API information",
            "GET /health": "Health check",
            "POST /predict": "Predict disease from leaf image"
        }
    })


@app.route('/health', methods=['GET'])
def health():
    """Health check endpoint."""
    return jsonify({
        "status": "healthy",
        "mode": "YOLO" if USE_YOLO else "MOCK",
        "model_loaded": USE_YOLO,
        "timestamp": datetime.now().isoformat()
    })


@app.route('/predict', methods=['POST'])
def predict():
    """
    Predict citrus leaf disease from uploaded image.
    Accepts multipart form data with field 'image'.
    """
    # Check if image field is present
    if 'image' not in request.files:
        return jsonify({
            "success": False,
            "message": "No image file provided. Use field name 'image'."
        }), 400

    file = request.files['image']

    # Check if a file was selected
    if file.filename == '':
        return jsonify({
            "success": False,
            "message": "No file selected."
        }), 400

    # Check file extension
    if not allowed_file(file.filename):
        return jsonify({
            "success": False,
            "message": "Invalid file type. Only JPG, JPEG, and PNG are allowed."
        }), 400

    try:
        # Save uploaded file temporarily
        temp_filename = f"upload_{uuid.uuid4().hex[:8]}.jpg"
        temp_path = os.path.join(RESULTS_DIR, temp_filename)
        file.save(temp_path)

        # Check file size (double-check after save)
        file_size = os.path.getsize(temp_path)
        if file_size > 5 * 1024 * 1024:
            os.remove(temp_path)
            return jsonify({
                "success": False,
                "message": "File size exceeds 5MB limit."
            }), 400

        # Run prediction
        if USE_YOLO:
            prediction = yolo_predict(temp_path)
        else:
            prediction = mock_predict(temp_path)

        # Clean up temp file
        if os.path.exists(temp_path):
            os.remove(temp_path)

        return jsonify({
            "success": True,
            "nama_penyakit": prediction["nama_penyakit"],
            "confidence": prediction["confidence"],
            "result_image": prediction["result_image"],
            "message": "OK"
        })

    except Exception as e:
        return jsonify({
            "success": False,
            "message": f"Error processing image: {str(e)}"
        }), 500


@app.route('/static/results/<filename>', methods=['GET'])
def serve_result(filename):
    """Serve result images."""
    return send_from_directory(RESULTS_DIR, filename)


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------

if __name__ == '__main__':
    print("=" * 60)
    print("  Citrus Leaf Disease Detection API")
    print(f"  Mode: {'YOLO (Real Inference)' if USE_YOLO else 'MOCK (Random Prediction)'}")
    print(f"  Port: 5000")
    print("=" * 60)
    app.run(host='0.0.0.0', port=5000, debug=True)
