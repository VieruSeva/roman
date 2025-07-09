import React, { useEffect } from 'react';
import { motion } from "framer-motion";
import AOS from "aos";
import "aos/dist/aos.css";
import { Link } from 'react-router-dom';
import { PageHero } from '../components/PageHero';
import { openDocumentPreview, downloadDocument, getDocumentUrl } from '../utils/documentPreview';
import { FaArrowLeft, FaFilePdf, FaDownload, FaFileWord, FaEye } from 'react-icons/fa';
import main3 from "../images/main3.jpg";

const LegislationPage = () => {
  // Modal handlers - simplified since we're opening in new tabs
  const handlePreview = (filename, title) => {
    const url = getDocumentUrl(filename);
    openDocumentPreview(url, title);
  };

  const handleDownload = (filename, title) => {
    const url = getDocumentUrl(filename);
    downloadDocument(url, filename);
  };

  useEffect(() => {
    // Initialize AOS animation library
    AOS.init({
      duration: 1000,
      once: true,
      easing: 'ease-in-out'
    });
    
    // Scroll to top when page loads
    window.scrollTo(0, 0);
  }, []);

  const pageInfo = {
    title: "Legislație",
    description: "Documente legislative importante pentru industria de panificație și morărit",
    breadcrumbs: [
      { name: "Acasă", path: "/" },
      { name: "Noutăți", path: "/noutati" },
      { name: "Legislație", path: "/noutati?category=legislatie" }
    ]
  };

  const documents = [
    {
      id: 1,
      title: "HOTĂRÂRE Cu privire la aprobarea Cerinţelor de calitate pentru produse de panificaţie şi paste făinoase",
      description: "Hotărârea privind cerințele de calitate pentru produsele de panificație și paste făinoase.",
      filename: "HOTĂRÂRE_Cerinţelor_Produse_de_panificaţie_şi_paste_făinoase.pdf",
      type: "pdf",
      icon: <FaFilePdf className="text-red-500 text-2xl" />
    },
    {
      id: 2,
      title: "Legea cu privire la comerțul interior",
      description: "Legea care reglementează activitățile de comerț interior.",
      filename: "Legea cu privirea la comerțul interior.pdf",
      type: "pdf",
      icon: <FaFilePdf className="text-red-500 text-2xl" />
    },
    {
      id: 3,
      title: "Legea privind calitatea apei potabile",
      description: "Legislația referitoare la calitatea apei potabile utilizate în industria alimentară.",
      filename: "Legea privind calitatea apei potabile.pdf",
      type: "pdf",
      icon: <FaFilePdf className="text-red-500 text-2xl" />
    },
    {
      id: 4,
      title: "Legea privind depozitarea cerealelor",
      description: "Legea care reglementează condițiile de depozitare a cerealelor.",
      filename: "Legea privind depozitatea cerialelor.pdf",
      type: "pdf",
      icon: <FaFilePdf className="text-red-500 text-2xl" />
    },
    {
      id: 5,
      title: "Legea privind siguranța alimentelor",
      description: "Legislația esențială privind siguranța alimentelor și normele de calitate.",
      filename: "Legea privind siguranța alimentelor.pdf",
      type: "pdf",
      icon: <FaFilePdf className="text-red-500 text-2xl" />
    },
    {
      id: 6,
      title: "Legea privind întreprinderile mici și mijlocii",
      description: "Cadrul legal pentru funcționarea întreprinderilor mici și mijlocii din industria alimentară.",
      filename: "Legea privind întreprinderile mici și mijlocii.pdf",
      type: "pdf",
      icon: <FaFilePdf className="text-red-500 text-2xl" />
    },
    {
      id: 7,
      title: "Legea privind informarea consumatorului cu privire la produse",
      description: "Legislația referitoare la informațiile ce trebuie furnizate consumatorilor despre produsele alimentare.",
      filename: "Legea_privind_informarea_consumatorului_cu_privire_la_produsele.pdf",
      type: "pdf",
      icon: <FaFilePdf className="text-red-500 text-2xl" />
    },
    {
      id: 8,
      title: "Industria Băuturilor",
      description: "Document referitor la industria băuturilor și reglementările aplicabile.",
      filename: "Industria Băuturilor .pdf",
      type: "pdf",
      icon: <FaFilePdf className="text-red-500 text-2xl" />
    }
  ];

  return (
    <motion.div
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      exit={{ opacity: 0 }}
      transition={{ duration: 0.5 }}
      className="legislation-page"
    >
      <PageHero 
        title={pageInfo.title} 
        description={pageInfo.description}
        breadcrumbs={pageInfo.breadcrumbs}
        bgImage={main3}
      />
      
      <section className="py-16 bg-white">
        <div className="container mx-auto px-4">
          <div className="max-w-4xl mx-auto">
            <div className="mb-12 text-center">
              <h2 className="text-3xl font-bold mb-4 font-heading">Documente Legislative</h2>
              <div className="w-24 h-1 bg-gradient-to-r from-primary-400 to-primary-600 mx-auto mb-6"></div>
              <p className="text-gray-600">
                Accesați documentele legislative importante pentru industria de panificație și morărit. 
                Aceste documente sunt esențiale pentru înțelegerea cadrului legal și normelor aplicabile în sector.
              </p>
            </div>
            
            <div className="grid gap-6">
              {documents.map((doc, index) => (
                <motion.div
                  key={doc.id}
                  initial={{ opacity: 0, y: 20 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  transition={{ duration: 0.5, delay: index * 0.1 }}
                  viewport={{ once: true }}
                  className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300"
                >
                  <div className="flex items-start">
                    <div className="mr-4 mt-1">
                      {doc.icon}
                    </div>
                    <div className="flex-1">
                      <h3 className="text-xl font-bold mb-2 font-heading text-gray-800">{doc.title}</h3>
                      <p className="text-gray-600 mb-4">{doc.description}</p>
                      <div className="flex items-center space-x-3">
                        <button
                          onClick={() => handlePreview(doc.filename, doc.title)}
                          className="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-md font-medium hover:bg-blue-600 transition-colors duration-300"
                        >
                          <FaEye className="mr-2" />
                          Previzualizare
                        </button>
                        <button
                          onClick={() => handleDownload(doc.filename, doc.title)}
                          className="inline-flex items-center px-4 py-2 bg-primary-500 text-white rounded-md font-medium hover:bg-primary-600 transition-colors duration-300"
                        >
                          <FaDownload className="mr-2" />
                          Descarcă {doc.type.toUpperCase()}
                        </button>
                      </div>
                    </div>
                  </div>
                </motion.div>
              ))}
            </div>
            
            <div className="mt-16 text-center">
              <Link 
                to="/noutati" 
                className="inline-flex items-center px-6 py-3 bg-primary-50 text-primary-600 rounded-full font-semibold hover:bg-primary-100 transition-colors duration-300"
              >
                <FaArrowLeft className="mr-2" />
                Înapoi la pagina de noutăți
              </Link>
            </div>
          </div>
        </div>
      </section>
    </motion.div>
  );
};

export default LegislationPage;

