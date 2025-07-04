from fastapi import FastAPI, HTTPException
from fastapi.responses import FileResponse
from fastapi.middleware.cors import CORSMiddleware
import os
import urllib.parse

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

@app.get("/")
async def root():
    return {"message": "Backend API is running"}

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