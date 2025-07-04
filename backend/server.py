from fastapi import FastAPI, HTTPException, Request
from fastapi.responses import FileResponse, HTMLResponse
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
import os
import urllib.parse
import requests
from bs4 import BeautifulSoup
import logging

app = FastAPI()

# Add CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Document directory
DOCUMENTS_DIR = "/app/frontend/public/documents"

# Set up logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class NewsImageRequest(BaseModel):
    url: str

def extract_image_from_url(url: str):
    """
    Extract image URL from news article URL
    Following the PHP guide exactly using og:image meta tag
    """
    try:
        # 1. Load HTML content from the news page (like PHP file_get_contents)
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        }
        
        response = requests.get(url, headers=headers, timeout=15)
        
        if response.status_code != 200:
            return {"error": f"Failed to load page: HTTP {response.status_code}"}

        html = response.text
        
        if not html:
            return {"error": "Empty response from server"}

        # 2. Parse HTML and look for og:image meta tag (like PHP DOMDocument)
        soup = BeautifulSoup(html, 'html.parser')
        
        # Look for Open Graph image meta tag
        og_image_tag = soup.find('meta', {'property': 'og:image'})
        
        if og_image_tag and og_image_tag.get('content'):
            image_url = og_image_tag.get('content')
            
            # Make sure it's an absolute URL
            if image_url.startswith('//'):
                image_url = 'https:' + image_url
            elif image_url.startswith('/'):
                # Relative URL
                from urllib.parse import urlparse
                parsed = urlparse(url)
                image_url = f"{parsed.scheme}://{parsed.netloc}{image_url}"
            elif not image_url.startswith(('http://', 'https://')):
                # Relative URL without leading slash
                from urllib.parse import urlparse
                parsed = urlparse(url)
                image_url = f"{parsed.scheme}://{parsed.netloc}/{image_url.lstrip('/')}"
            
            # Also extract title and description
            title = None
            og_title = soup.find('meta', {'property': 'og:title'})
            if og_title and og_title.get('content'):
                title = og_title.get('content').strip()
            else:
                title_tag = soup.find('title')
                if title_tag:
                    title = title_tag.get_text().strip()
            
            description = None
            og_desc = soup.find('meta', {'property': 'og:description'})
            if og_desc and og_desc.get('content'):
                description = og_desc.get('content').strip()
            else:
                meta_desc = soup.find('meta', {'name': 'description'})
                if meta_desc and meta_desc.get('content'):
                    description = meta_desc.get('content').strip()
            
            return {
                "image_url": image_url,
                "title": title,
                "description": description
            }
        else:
            return {"error": "No og:image meta tag found"}

    except requests.RequestException as e:
        logger.error(f"HTTP Request failed: {str(e)}")
        return {"error": f"Failed to fetch page: {str(e)}"}
    except Exception as e:
        logger.error(f"Error extracting image: {str(e)}")
        return {"error": f"Error processing page: {str(e)}"}

@app.get("/")
async def root():
    return {"message": "Backend API is running"}

@app.post("/api/fetch-news-image")
async def fetch_news_image(request: NewsImageRequest):
    """
    Fetch image from news article URL
    Simple implementation following PHP guide
    """
    try:
        url = request.url
        
        # Extract image using our rewritten function
        result = extract_image_from_url(url)

        # Return clean response
        if "error" in result:
            return {
                "success": False,
                "url": url,
                "error": result["error"],
                "image_url": None,
                "title": None,
                "description": None
            }

        return {
            "success": True,
            "url": url,
            "image_url": result.get("image_url"),
            "title": result.get("title"),
            "description": result.get("description"),
            "error": None
        }

    except Exception as e:
        logger.error(f"Error in fetch_news_image: {str(e)}")
        return {
            "success": False,
            "error": "Server error occurred",
            "details": str(e)
        }

@app.post("/api/fetch-multiple-news-images")
async def fetch_multiple_news_images(request: Request):
    """
    Fetch images from multiple news article URLs
    Returns array format for frontend compatibility
    """
    try:
        urls = await request.json()
        
        if not isinstance(urls, list) or not urls:
            return []

        results = []
        
        for url in urls:
            # Validate each URL
            if not isinstance(url, str) or not url.startswith(('http://', 'https://')):
                results.append({
                    "url": url,
                    "image_url": None,
                    "title": None,
                    "description": None,
                    "error": "Invalid URL format"
                })
                continue

            result = extract_image_from_url(url)
            
            if "error" in result:
                results.append({
                    "url": url,
                    "image_url": None,
                    "title": None,
                    "description": None,
                    "error": result["error"]
                })
            else:
                results.append({
                    "url": url,
                    "image_url": result.get("image_url"),
                    "title": result.get("title"),
                    "description": result.get("description"),
                    "error": None
                })

        return results

    except Exception as e:
        logger.error(f"Error fetching multiple news images: {str(e)}")
        return []

@app.get("/api/test-image-extraction", response_class=HTMLResponse)
async def test_image_extraction():
    """Test page for image extraction"""
    html_content = '''
    <!DOCTYPE html>
    <html>
    <head>
        <title>Test Image Extraction</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
                line-height: 1.6;
            }
            h1 {
                color: #4a5568;
                border-bottom: 2px solid #edf2f7;
                padding-bottom: 10px;
            }
            .test-section {
                background-color: #f8fafc;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }
            .form-group {
                margin: 15px 0;
            }
            label {
                display: block;
                margin-bottom: 5px;
                font-weight: bold;
            }
            input[type="url"] {
                width: 100%;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 16px;
            }
            button {
                background-color: #4299e1;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
            }
            button:hover {
                background-color: #3182ce;
            }
            .result {
                margin-top: 20px;
                padding: 15px;
                border-radius: 4px;
                background-color: #e2e8f0;
            }
            .success {
                background-color: #c6f6d5;
                border: 1px solid #68d391;
            }
            .error {
                background-color: #fed7d7;
                border: 1px solid #fc8181;
            }
            .image-preview {
                max-width: 100%;
                max-height: 300px;
                border: 1px solid #ddd;
                border-radius: 4px;
                margin: 10px 0;
            }
        </style>
    </head>
    <body>
        <h1>Test Image Extraction from News URLs</h1>
        
        <div class="test-section">
            <h2>Test Single URL</h2>
            <div class="form-group">
                <label for="news-url">Enter News Article URL:</label>
                <input type="url" id="news-url" placeholder="https://example.com/news/article">
            </div>
            <button onclick="testSingleUrl()">Extract Image</button>
            <div id="single-result"></div>
        </div>

        <div class="test-section">
            <h2>Test Sample URLs</h2>
            <p>Click to test with sample news URLs:</p>
            <button onclick="testSampleUrls()">Test Sample URLs</button>
            <div id="sample-results"></div>
        </div>
        
        <script>
            async function testSingleUrl() {
                const url = document.getElementById("news-url").value;
                const resultDiv = document.getElementById("single-result");
                
                if (!url) {
                    resultDiv.innerHTML = '<div class="result error">Please enter a URL</div>';
                    return;
                }
                
                resultDiv.innerHTML = '<div class="result">Loading...</div>';
                
                try {
                    const response = await fetch("/api/fetch-news-image", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify({ url: url })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success && data.image_url) {
                        resultDiv.innerHTML = `
                            <div class="result success">
                                <h3>✅ Success!</h3>
                                <p><strong>URL:</strong> ${data.url}</p>
                                <p><strong>Title:</strong> ${data.title || "Not found"}</p>
                                <p><strong>Description:</strong> ${data.description || "Not found"}</p>
                                <p><strong>Image URL:</strong> <a href="${data.image_url}" target="_blank">${data.image_url}</a></p>
                                <img src="${data.image_url}" alt="Extracted Image" class="image-preview" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                <p style="display:none; color:red;">⚠️ Image failed to load</p>
                            </div>
                        `;
                    } else {
                        resultDiv.innerHTML = `
                            <div class="result error">
                                <h3>❌ Error</h3>
                                <p><strong>URL:</strong> ${data.url}</p>
                                <p><strong>Error:</strong> ${data.error}</p>
                            </div>
                        `;
                    }
                } catch (error) {
                    resultDiv.innerHTML = `
                        <div class="result error">
                            <h3>❌ Request Failed</h3>
                            <p><strong>Error:</strong> ${error.message}</p>
                        </div>
                    `;
                }
            }
            
            async function testSampleUrls() {
                const sampleUrls = [
                    "https://stiri.md/article/social/tot-mai-multi-pasionati-de-panificatie-descopera-farmecul-painii-cu-maia/",
                    "https://agora.md/2025/02/21/cel-mai-mare-producator-din-industria-de-panificatie-din-moldova-inregistreaza-un-profit-record",
                    "https://mded.gov.md/domenii/ajutor-de-stat/ajutor-de-stat-regional-pentru-investitii/"
                ];
                
                const resultDiv = document.getElementById("sample-results");
                resultDiv.innerHTML = '<div class="result">Testing sample URLs...</div>';
                
                try {
                    const response = await fetch("/api/fetch-multiple-news-images", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify(sampleUrls)
                    });
                    
                    const data = await response.json();
                    
                    let resultsHtml = `<div class="result"><h3>Results for ${data.total} URLs:</h3>`;
                    
                    data.results.forEach((result, index) => {
                        if (result.success && result.image_url) {
                            resultsHtml += `
                                <div style="margin: 15px 0; padding: 10px; border: 1px solid #68d391; border-radius: 4px; background-color: #c6f6d5;">
                                    <strong>URL ${index + 1}:</strong> ✅ Success<br>
                                    <strong>Title:</strong> ${result.title || "Not found"}<br>
                                    <strong>Image:</strong> <a href="${result.image_url}" target="_blank">${result.image_url}</a><br>
                                    <img src="${result.image_url}" alt="Image ${index + 1}" style="max-width: 200px; max-height: 150px; margin: 5px 0;">
                                </div>
                            `;
                        } else {
                            resultsHtml += `
                                <div style="margin: 15px 0; padding: 10px; border: 1px solid #fc8181; border-radius: 4px; background-color: #fed7d7;">
                                    <strong>URL ${index + 1}:</strong> ❌ ${result.error}<br>
                                    <strong>URL:</strong> ${result.url}
                                </div>
                            `;
                        }
                    });
                    
                    resultsHtml += `</div>`;
                    resultDiv.innerHTML = resultsHtml;
                    
                } catch (error) {
                    resultDiv.innerHTML = `
                        <div class="result error">
                            <h3>❌ Request Failed</h3>
                            <p><strong>Error:</strong> ${error.message}</p>
                        </div>
                    `;
                }
            }
        </script>
    </body>
    </html>'''
    
    return HTMLResponse(content=html_content)

@app.get("/api/download/{filename}")
async def download_file(filename: str):
    """Download a document file"""
    try:
        # Decode the filename to handle special characters
        decoded_filename = urllib.parse.unquote(filename)
        
        # Common filename mappings
        filename_mappings = {
            "oferta-lactate-ro.pdf": "Ofertă Lactate RO .pdf",
            "industria-bauturilor.pdf": "Industria Băuturilor .pdf",
            "oferta-carne-si-oua-ro.pdf": "Ofertă Carne și Ouă RO .pdf",
            "tehnologii-si-inovatii-1.pdf": "tehnologii_si_inovatii1.pdf",
            "tehnologii-si-inovatii-2.pdf": "tehnologii_si_inovatii2.pdf",
            "tehnologii-si-inovatii-3.pdf": "tehnologii_si_inovatii3.pdf",
            "tehnologii-si-inovatii-4.pdf": "tehnologii_si_inovatii4.pdf",
            "tehnologii-si-inovatii-5.pdf": "tehnologii_si_inovatii5.pdf",
            "materii-prime-1.docx": "materii_prime1.docx",
            "minist-1.pdf": "minist1.pdf",
            "minist-2.pdf": "minist2.pdf",
            "minist-3.docx": "minist3.docx",
            "minist-4.pdf": "minist4.pdf",
            "memorandum-1.docx": "memorandum1.docx",
            "memorandum-2.docx": "memorandum2.docx"
        }
        
        # Check if there's a mapping for this filename
        actual_filename = filename_mappings.get(decoded_filename, decoded_filename)
        
        # Construct the full file path
        file_path = os.path.join(DOCUMENTS_DIR, actual_filename)
        
        # Check if file exists
        if not os.path.exists(file_path):
            # Try with the original filename if mapping doesn't work
            file_path = os.path.join(DOCUMENTS_DIR, decoded_filename)
            if not os.path.exists(file_path):
                raise HTTPException(status_code=404, detail=f"File not found: {decoded_filename}")
        
        # Return the file
        return FileResponse(
            path=file_path,
            filename=actual_filename,
            media_type='application/octet-stream'
        )
    
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error downloading file: {str(e)}")

@app.get("/api/documents")
async def list_documents():
    """List all available documents"""
    try:
        if not os.path.exists(DOCUMENTS_DIR):
            return {"documents": []}
        
        documents = []
        for filename in os.listdir(DOCUMENTS_DIR):
            if filename.endswith(('.pdf', '.docx', '.doc')):
                file_path = os.path.join(DOCUMENTS_DIR, filename)
                file_size = os.path.getsize(file_path)
                documents.append({
                    "filename": filename,
                    "size": file_size,
                    "download_url": f"/api/download/{urllib.parse.quote(filename)}"
                })
        
        return {"documents": documents}
    
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error listing documents: {str(e)}")

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8001)