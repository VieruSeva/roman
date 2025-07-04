import React, { useEffect, useState } from 'react';
import { motion } from "framer-motion";
import AOS from "aos";
import "aos/dist/aos.css";
import axios from "axios";
import { useLocation, Link } from 'react-router-dom';
import { Hero } from '../components/Hero';
import { About } from '../components/About';
import { Contact } from '../components/Contact';
import { FaUsers, FaHistory, FaLandmark, FaStar, FaCalendarAlt, FaTag, FaUser, FaSearch, FaClock, FaExternalLinkAlt, FaChevronRight, FaMapMarkerAlt, FaFlask, FaFileAlt, FaImage } from 'react-icons/fa';
import ministerImage from "../images/minister.jpg";

const BACKEND_URL = process.env.REACT_APP_BACKEND_URL;
const API = `${BACKEND_URL}/api`;

const HomePage = () => {
  const location = useLocation();
  const searchParams = new URLSearchParams(location.search);
  const section = searchParams.get('section');
  
  // State for latest news section
  const [expandedItemId, setExpandedItemId] = useState(null);
  const [newsImages, setNewsImages] = useState({});
  const [loadingImages, setLoadingImages] = useState(true);
  const [previewModal, setPreviewModal] = useState({
    isOpen: false,
    url: '',
    title: ''
  });

  // Latest news data (copied from NewsPage)
  const latestNewsData = [
    {
      id: 9,
      title: "Tranziția Verde a Republicii Moldova: Motor al Integrării Europene și Dezvoltării Durabile",
      excerpt: "Pe 24 iunie, la Maib Park, a avut loc conferința națională \"Tranziția verde a Republicii Moldova: Un motor al integrării europene și al creșterii durabile\". Evenimentul a fost organizat de Uniunea Europeană și Programul Națiunilor Unite pentru Dezvoltare (PNUD).",
      date: "24 iunie 2025",
      author: "ANIPM",
      category: "sustenabilitate",
      image: "/siteik/images/trans1.jpg",
      readTime: "10 min",
      url: "#",
      hasImages: true
    },
    {
      id: 8,
      title: "Ministerul Agriculturii vine cu o reacție în urma demersului scris de ANIPM",
      excerpt: "Ministerul Agriculturii și Industriei Alimentare a transmis un răspuns oficial în urma demersului înaintat de Asociația Națională a Industriei de Panificație și Morărit din Moldova. Documentele oficiale sunt disponibile pentru consultare.",
      date: "5 aprilie 2025",
      author: "ANIPM",
      category: "oficial",
      image: ministerImage,
      readTime: "8 min",
      url: "#",
      hasDocuments: true,
      documents: [
        {
          id: 1,
          title: "Răspuns oficial - Partea 1",
          filename: "minist1.pdf",
          url: `${process.env.REACT_APP_BACKEND_URL || ''}/api/download/minist1.pdf`
        },
        {
          id: 2,
          title: "Răspuns oficial - Partea 2", 
          filename: "minist2.pdf",
          url: `${process.env.REACT_APP_BACKEND_URL || ''}/api/download/minist2.pdf`
        },
        {
          id: 3,
          title: "Anexă - Document de poziție",
          filename: "minist3.docx",
          url: `${process.env.REACT_APP_BACKEND_URL || ''}/api/download/minist3.docx`
        },
        {
          id: 4,
          title: "Răspuns oficial - Partea 3",
          filename: "minist4.pdf", 
          url: `${process.env.REACT_APP_BACKEND_URL || ''}/api/download/minist4.pdf`
        }
      ]
    },
    {
      id: 7,
      title: "Schema de ajutor de stat regional pentru investiții",
      excerpt: "Citește despre programul de ajutor de stat pentru investiții regionale pe site-ul oficial al MDED.",
      date: "30 martie 2025",
      author: "MDED.gov.md",
      category: "programe",
      image: "https://images.unsplash.com/photo-1551295022-de5522c94e08",
      readTime: "6 min",
      url: "https://mded.gov.md/domenii/ajutor-de-stat/ajutor-de-stat-regional-pentru-investitii/"
    },
    {
      id: 6,
      title: "R. Moldova exportă mai multă făină, dar la un preț mult mai mic",
      excerpt: "Citește despre situația exporturilor de făină din Moldova pe site-ul original.",
      date: "29 martie 2025",
      author: "Agroexpert.md",
      category: "piata",
      image: null,
      readTime: "4 min",
      url: "https://agroexpert.md/rom/novosti/r-moldova-exporta-mai-multa-faina-dar-la-un-pret-mult-mai-mic"
    },
    {
      id: 1,
      title: "Tot mai mulți pasionați de panificație descoperă farmecul pâinii cu maia",
      excerpt: "Afla mai multe despre trendul pâinii cu maia pe site-ul original.",
      date: "15 ianuarie 2025",
      author: "Stiri.md",
      category: "panificatie",
      image: null,
      readTime: "5 min",
      url: "https://stiri.md/article/social/tot-mai-multi-pasionati-de-panificatie-descopera-farmecul-painii-cu-maia/"
    },
    {
      id: 2,
      title: "În Transnistria se vor scumpi făina și pâinea",
      excerpt: "Citește despre modificările de prețuri pe site-ul original.",
      date: "12 ianuarie 2025",
      author: "Stiri.md",
      category: "piata",
      image: null,
      readTime: "3 min",
      url: "https://stiri.md/article/economic/in-transnistria-se-vor-scumpi-faina-si-painea/"
    },
    {
      id: 3,
      title: "Ion Perju: Prețurile s-au majorat nejustificat, grâu în țară este",
      excerpt: "Află mai multe despre declarațiile oficiale pe site-ul original.",
      date: "10 ianuarie 2025",
      author: "Stiri.md",
      category: "piata",
      image: null,
      readTime: "4 min",
      url: "https://stiri.md/article/economic/ion-perju-preturile-s-au-majorat-nejustificat-grau-in-tara-este/"
    },
    {
      id: 4,
      title: "Cel mai mare producător din industria de panificație din Moldova înregistrează un profit record",
      excerpt: "Citește analiza completă pe site-ul original.",
      date: "21 februarie 2025",
      author: "Agora.md",
      category: "business",
      image: null,
      readTime: "6 min",
      url: "https://agora.md/2025/02/21/cel-mai-mare-producator-din-industria-de-panificatie-din-moldova-inregistreaza-un-profit-record"
    },
    {
      id: 5,
      title: "De ce brutarii din Chișinău coc din ce în ce mai puțină pâine",
      excerpt: "Afla mai multe despre această analiză pe site-ul original.",
      date: "15 decembrie 2024",
      author: "Europa Liberă",
      category: "tendinte",
      image: null,
      readTime: "7 min",
      url: "https://moldova.europalibera.org/a/27188328.html"
    }
  ];
  
  const helloWorldApi = async () => {
    try {
      const response = await axios.get(`${API}/`);
      console.log(response.data.message);
    } catch (e) {
      console.error(e, `errored out requesting / api`);
    }
  };

  useEffect(() => {
    helloWorldApi();
    
    // Initialize AOS animation library with enhanced settings
    AOS.init({
      duration: 1000,
      once: true,
      easing: 'ease-in-out',
      mirror: true,
      anchorPlacement: 'top-bottom'
    });
    
    // If section is specified in URL, scroll to that section
    if (section === 'despre-noi') {
      setTimeout(() => {
        const aboutSection = document.getElementById('despre-noi');
        if (aboutSection) {
          aboutSection.scrollIntoView({ behavior: 'smooth' });
        }
      }, 500);
    }
  }, [section]);

  return (
    <motion.div 
      className="overflow-hidden"
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      exit={{ opacity: 0 }}
      transition={{ duration: 0.5 }}
    >
      <Hero />
      
      {/* About section with isFullPage=true to show more content */}
      <About isFullPage={true} />
      
      {/* Additional content from AboutPage */}
      <section className="py-20 bg-white">
        <div className="container mx-auto px-4">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
            {[
              {
                icon: <FaHistory className="text-4xl text-primary-500" />,
                title: "Istoria Noastră",
                description: "Fondată în anul 2015, asociația noastră are o istorie bogată în sprijinirea industriei panificației și morăritului moldovenești."
              },
              {
                icon: <FaUsers className="text-4xl text-primary-500" />,
                title: "Echipa Noastră",
                description: "Avem o echipă dedicată de profesioniști cu experiență în domeniul panificației și morăritului."
              },
              {
                icon: <FaLandmark className="text-4xl text-primary-500" />,
                title: "Valori Fundamentale",
                description: "Suntem ghidați de valori precum integritatea, calitatea și sustenabilitatea în tot ceea ce facem."
              },
              {
                icon: <FaStar className="text-4xl text-primary-500" />,
                title: "Realizări",
                description: "Am contribuit semnificativ la dezvoltarea și modernizarea industriei agricole din Moldova."
              }
            ].map((item, index) => (
              <motion.div
                key={index}
                initial={{ opacity: 0, y: 30 }}
                whileInView={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.5, delay: index * 0.1 }}
                viewport={{ once: true }}
                className="bg-white p-8 rounded-xl shadow-lg border border-gray-100 hover:shadow-xl transition-shadow duration-300"
              >
                <div className="mb-4 text-primary-500">{item.icon}</div>
                <h3 className="text-xl font-bold mb-3 font-heading">{item.title}</h3>
                <p className="text-gray-600">{item.description}</p>
              </motion.div>
            ))}
          </div>
        </div>
      </section>
      
      <MainPageNews />
      <Contact />
    </motion.div>
  );
};

export default HomePage;