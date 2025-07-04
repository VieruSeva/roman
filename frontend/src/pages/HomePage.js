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
  
  // Helper functions for latest news section
  const toggleReadMore = (id) => {
    setExpandedItemId(expandedItemId === id ? null : id);
  };

  const openPreview = (url, title) => {
    setPreviewModal({
      isOpen: true,
      url: url,
      title: title
    });
  };

  const closePreview = () => {
    setPreviewModal({
      isOpen: false,
      url: '',
      title: ''
    });
  };

  const downloadFile = async (url, filename) => {
    try {
      const response = await fetch(url);
      const blob = await response.blob();
      const downloadUrl = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = downloadUrl;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(downloadUrl);
    } catch (error) {
      console.error('Error downloading file:', error);
      alert('Eroare la descărcarea fișierului. Încercați din nou.');
    }
  };

  // Animation variants
  const cardVariants = {
    hidden: { opacity: 0, y: 30 },
    visible: (i) => ({
      opacity: 1,
      y: 0,
      transition: {
        delay: i * 0.1,
        duration: 0.5,
        ease: "easeOut"
      }
    })
  };

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

  // Fetch images for latest news items when component mounts
  useEffect(() => {
    const fetchNewsImages = async () => {
      try {
        const urls = latestNewsData
          .filter(news => news.url && news.url !== '#')
          .map(news => news.url);
        
        console.log('Fetching images for URLs:', urls);
        
        if (urls.length === 0) {
          console.log('No external URLs found');
          setLoadingImages(false);
          return;
        }

        const backendUrl = process.env.REACT_APP_BACKEND_URL || window.location.origin;
        console.log('Backend URL:', backendUrl);
        
        const apiUrl = `${backendUrl}/api/fetch-multiple-news-images`;
        console.log('Calling API:', apiUrl);
        
        const response = await fetch(apiUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(urls)
        });

        console.log('API Response status:', response.status);

        if (response.ok) {
          const results = await response.json();
          console.log('API Results:', results);
          
          const imageMap = {};
          results.forEach(result => {
            if (result.image_url) {
              imageMap[result.url] = {
                image_url: result.image_url,
                title: result.title,
                description: result.description
              };
            }
          });
          
          console.log('Image Map:', imageMap);
          setNewsImages(imageMap);
        } else {
          console.error('API call failed:', response.status, await response.text());
        }
      } catch (error) {
        console.error('Error fetching news images:', error);
      } finally {
        setLoadingImages(false);
      }
    };

    fetchNewsImages();
  }, []);

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
      
      {/* LATEST NEWS SECTION - Modern Polished Design with External Images (copied from NewsPage) */}
      <section className="py-20 bg-gradient-to-br from-gray-50 via-white to-gray-50">
        <div className="container mx-auto px-4">
          <div className="text-center mb-16">
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6 }}
              viewport={{ once: true }}
            >
              <h2 className="text-4xl md:text-5xl font-bold mb-4 text-gray-900 font-heading">
                Ultimele <span className="text-primary-600">Știri</span>
              </h2>
              <div className="w-24 h-1 bg-gradient-to-r from-primary-500 to-blue-500 mx-auto mb-6 rounded-full"></div>
              <p className="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
                Cele mai recente știri și actualizări din industria de panificație și morărit, cu imagini și informații de la sursele originale
              </p>
            </motion.div>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-7xl mx-auto">
            {latestNewsData.slice(0, 9).map((news, index) => (
              <motion.div
                key={news.id}
                custom={index}
                initial="hidden"
                whileInView="visible"
                viewport={{ once: true }}
                variants={cardVariants}
                className="group"
              >
                {news.url && news.url !== '#' ? (
                  <a 
                    href={news.url} 
                    target="_blank" 
                    rel="noopener noreferrer"
                    className="block h-full"
                  >
                    <div className="bg-white rounded-2xl overflow-hidden shadow-lg border border-gray-100 hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-3 hover:border-primary-200 h-full flex flex-col">
                      {/* Image Section with Static and External Fetching */}
                      <div className="relative h-56 overflow-hidden">
                        {loadingImages ? (
                          <div className="h-full bg-gradient-to-br from-gray-200 via-gray-300 to-gray-200 animate-pulse flex items-center justify-center">
                            <div className="text-center">
                              <FaImage className="text-gray-400 text-4xl mx-auto mb-2 animate-pulse" />
                              <div className="text-gray-500 text-sm">Se încarcă imaginea...</div>
                            </div>
                          </div>
                        ) : news.image ? (
                          <>
                            <img 
                              src={news.image} 
                              alt={news.title} 
                              className="w-full h-full object-cover transition-all duration-700 group-hover:scale-110"
                            />
                          </>
                        ) : newsImages[news.url]?.image_url ? (
                          <>
                            <img 
                              src={newsImages[news.url].image_url} 
                              alt={news.title} 
                              className="w-full h-full object-cover transition-all duration-700 group-hover:scale-110"
                              onError={(e) => {
                                e.target.style.display = 'none';
                                e.target.nextSibling.style.display = 'flex';
                              }}
                            />
                            {/* Source Attribution Overlay */}
                            <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-4">
                              <div className="flex items-center justify-between">
                                <div className="flex items-center space-x-2">
                                  <div className="w-8 h-8 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-white font-bold text-sm border border-white/30">
                                    {news.author.charAt(0)}
                                  </div>
                                  <div>
                                    <div className="text-white text-sm font-semibold">{news.author}</div>
                                    <div className="text-white/80 text-xs">Sursă externă</div>
                                  </div>
                                </div>
                                <div className="text-white/90 text-xs bg-white/20 backdrop-blur-sm px-2 py-1 rounded-full border border-white/30">
                                  🌐 Extern
                                </div>
                              </div>
                            </div>
                          </>
                        ) : (
                          <div className="h-full bg-gradient-to-br from-gray-100 via-blue-50 to-blue-100 flex items-center justify-center">
                            <div className="text-center">
                              <FaImage className="text-blue-400 text-4xl mx-auto mb-2" />
                              <div className="text-blue-600 text-sm font-medium">Se încarcă imaginea...</div>
                            </div>
                          </div>
                        )}
                        
                        {/* Fallback image div */}
                        <div className="fallback-image h-full bg-gradient-to-br from-gray-100 via-blue-50 to-blue-100 hidden items-center justify-center">
                          <div className="text-center">
                            <FaImage className="text-blue-400 text-4xl mx-auto mb-2" />
                            <div className="text-blue-600 text-sm font-medium">Imagine indisponibilă</div>
                          </div>
                        </div>
                        
                        {/* Category badge */}
                        <div className="absolute top-4 right-4 bg-gradient-to-r from-primary-500 to-blue-500 text-white text-xs font-bold py-2 px-3 rounded-full shadow-lg border border-white/20 uppercase tracking-wide">
                          🌐 Extern
                        </div>
                      </div>
                      
                      {/* Content Section */}
                      <div className="p-6 flex flex-col flex-grow">
                        {/* Tags and Meta Info */}
                        <div className="flex flex-wrap gap-2 mb-4">
                          <span className="px-3 py-1 bg-gradient-to-r from-primary-100 to-blue-100 text-primary-700 rounded-full text-xs font-semibold inline-flex items-center border border-primary-200">
                            <FaCalendarAlt className="mr-1" /> {news.date}
                          </span>
                          <span className="px-3 py-1 bg-gradient-to-r from-blue-100 to-purple-100 text-blue-700 rounded-full text-xs font-semibold inline-flex items-center border border-blue-200">
                            <FaTag className="mr-1" /> {news.category.charAt(0).toUpperCase() + news.category.slice(1)}
                          </span>
                          <span className="px-3 py-1 bg-gradient-to-r from-gray-100 to-gray-200 text-gray-700 rounded-full text-xs font-semibold inline-flex items-center border border-gray-200">
                            <FaClock className="mr-1" /> {news.readTime}
                          </span>
                        </div>
                        
                        {/* Title */}
                        <h3 className="text-xl font-bold mb-3 text-gray-900 group-hover:text-primary-600 transition-colors duration-300 line-clamp-2 leading-tight">
                          {news.title}
                        </h3>
                        
                        {/* Excerpt */}
                        <p className="text-gray-600 mb-6 flex-grow line-clamp-3 leading-relaxed text-sm">
                          {news.excerpt}
                        </p>
                        
                        {/* Footer with Author and CTA */}
                        <div className="flex items-center justify-between pt-4 border-t border-gray-100 mt-auto">
                          <div className="flex items-center space-x-3">
                            <div className="w-10 h-10 rounded-full bg-gradient-to-br from-primary-500 to-blue-500 flex items-center justify-center text-white font-bold text-sm shadow-lg">
                              {news.author.charAt(0)}
                            </div>
                            <div>
                              <div className="text-sm font-semibold text-gray-800">{news.author}</div>
                              <div className="text-xs text-gray-500">Sursă externă</div>
                            </div>
                          </div>
                          
                          <div className="flex items-center text-primary-600 font-semibold group-hover:text-primary-700 transition-colors duration-300">
                            <span className="text-sm mr-2">Citește</span>
                            <div className="w-8 h-8 rounded-full bg-gradient-to-r from-primary-100 to-blue-100 group-hover:from-primary-200 group-hover:to-blue-200 flex items-center justify-center transition-all duration-300 shadow-md">
                              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M14 5l7 7m0 0l-7 7m7-7H3" />
                              </svg>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </a>
                ) : news.id === 9 ? (
                  // Special handling for Green Transition news - clickable card to gallery
                  <Link 
                    to={`/noutati/${news.id}`}
                    className="block h-full"
                  >
                    <div className="bg-white rounded-2xl overflow-hidden shadow-lg border border-gray-100 hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-3 hover:border-green-200 h-full flex flex-col group">
                      {/* Image Section for Green Transition */}
                      <div className="relative h-56 overflow-hidden">
                        {news.image ? (
                          <>
                            <img 
                              src={news.image} 
                              alt={news.title} 
                              className="w-full h-full object-cover transition-all duration-700 group-hover:scale-110"
                            />
                            {/* Green badge for image gallery */}
                            <div className="absolute top-4 right-4 bg-gradient-to-r from-green-600 to-emerald-600 text-white text-xs font-bold py-2 px-3 rounded-full shadow-lg border border-white/20">
                              <div className="flex items-center space-x-1">
                                <span>🖼️</span>
                                <span>IMAGINE</span>
                              </div>
                            </div>
                          </>
                        ) : (
                          <div className="h-full bg-gradient-to-br from-green-100 via-emerald-100 to-green-200 flex items-center justify-center">
                            <div className="text-center">
                              <FaImage className="text-green-500 text-4xl mx-auto mb-2" />
                              <div className="text-green-700 text-sm font-medium">Galerie foto</div>
                              <div className="text-green-600 text-xs mt-1">Click pentru galerie</div>
                            </div>
                          </div>
                        )}
                      </div>
                      
                      {/* Content Section */}
                      <div className="p-6 flex flex-col flex-grow">
                        {/* Tags and Meta Info */}
                        <div className="flex flex-wrap gap-2 mb-4">
                          <span className="px-3 py-1 bg-gradient-to-r from-green-100 to-emerald-100 text-green-700 rounded-full text-xs font-semibold inline-flex items-center border border-green-200">
                            <FaCalendarAlt className="mr-1" /> {news.date}
                          </span>
                          <span className="px-3 py-1 bg-gradient-to-r from-emerald-100 to-teal-100 text-emerald-700 rounded-full text-xs font-semibold inline-flex items-center border border-emerald-200">
                            <FaTag className="mr-1" /> {news.category.charAt(0).toUpperCase() + news.category.slice(1)}
                          </span>
                          <span className="px-3 py-1 bg-gradient-to-r from-blue-100 to-indigo-100 text-blue-700 rounded-full text-xs font-semibold inline-flex items-center border border-blue-200">
                            🌱 Conferință
                          </span>
                        </div>
                        
                        {/* Title */}
                        <h3 className="text-xl font-bold mb-3 text-gray-900 group-hover:text-green-600 transition-colors duration-300 line-clamp-2 leading-tight">
                          {news.title}
                        </h3>
                        
                        {/* Excerpt */}
                        <p className="text-gray-600 mb-4 flex-grow line-clamp-3 leading-relaxed text-sm">
                          {news.excerpt}
                        </p>
                        
                        {/* Footer with Author and Gallery Info */}
                        <div className="flex items-center justify-between pt-4 border-t border-gray-100 mt-auto">
                          <div className="flex items-center space-x-3">
                            <div className="w-10 h-10 rounded-full bg-gradient-to-br from-green-500 to-emerald-500 flex items-center justify-center text-white font-bold text-sm shadow-lg">
                              {news.author.charAt(0)}
                            </div>
                            <div>
                              <div className="text-sm font-semibold text-gray-800">{news.author}</div>
                              <div className="text-xs text-gray-500">Galerie foto</div>
                            </div>
                          </div>
                          
                          <div className="flex items-center text-green-600 font-semibold group-hover:text-green-700 transition-colors duration-300">
                            <span className="text-sm mr-2">🖼️ Vezi galeria</span>
                            <div className="w-8 h-8 rounded-full bg-gradient-to-r from-green-100 to-emerald-100 group-hover:from-green-200 group-hover:to-emerald-200 flex items-center justify-center transition-all duration-300 shadow-md">
                              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M14 5l7 7m0 0l-7 7m7-7H3" />
                              </svg>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </Link>
                ) : (
                  <div className="bg-white rounded-2xl overflow-hidden shadow-lg border border-gray-100 hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-3 hover:border-primary-200 h-full flex flex-col">
                    {/* Image Section for News with Documents */}
                    <div className="relative h-56 overflow-hidden">
                      {news.image ? (
                        <>
                          <img 
                            src={news.image} 
                            alt={news.title} 
                            className="w-full h-full object-cover transition-all duration-700 group-hover:scale-110"
                          />
                          {/* Blue badge for documents */}
                          <div className="absolute top-4 right-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-xs font-bold py-2 px-3 rounded-full shadow-lg border border-white/20">
                            <div className="flex items-center space-x-1">
                              <span>📄</span>
                              <span>DOCUMENTE</span>
                            </div>
                          </div>
                        </>
                      ) : (
                        <div className="h-full bg-gradient-to-br from-blue-100 via-indigo-100 to-blue-200 flex items-center justify-center">
                          <div className="text-center">
                            <FaImage className="text-blue-500 text-4xl mx-auto mb-2" />
                            <div className="text-blue-700 text-sm font-medium">Documente oficiale</div>
                            <div className="text-blue-600 text-xs mt-1">Pentru previzualizare</div>
                          </div>
                        </div>
                      )}
                    </div>
                    
                    {/* Content Section */}
                    <div className="p-6 flex flex-col flex-grow">
                      {/* Tags and Meta Info */}
                      <div className="flex flex-wrap gap-2 mb-4">
                        <span className="px-3 py-1 bg-gradient-to-r from-blue-100 to-indigo-100 text-blue-700 rounded-full text-xs font-semibold inline-flex items-center border border-blue-200">
                          <FaCalendarAlt className="mr-1" /> {news.date}
                        </span>
                        <span className="px-3 py-1 bg-gradient-to-r from-indigo-100 to-purple-100 text-indigo-700 rounded-full text-xs font-semibold inline-flex items-center border border-indigo-200">
                          <FaTag className="mr-1" /> {news.category.charAt(0).toUpperCase() + news.category.slice(1)}
                        </span>
                        <span className="px-3 py-1 bg-gradient-to-r from-green-100 to-emerald-100 text-green-700 rounded-full text-xs font-semibold inline-flex items-center border border-green-200">
                          🏛️ Ministerial
                        </span>
                      </div>
                      
                      {/* Title */}
                      <h3 className="text-xl font-bold mb-3 text-gray-900 line-clamp-2 leading-tight">
                        {news.title}
                      </h3>
                      
                      {/* Excerpt */}
                      <p className="text-gray-600 mb-4 flex-grow line-clamp-3 leading-relaxed text-sm">
                        {news.excerpt}
                      </p>
                      
                      {/* Documents Section */}
                      {news.hasDocuments && news.documents && (
                        <div className="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-4 mb-4 border border-blue-100">
                          <h4 className="text-sm font-semibold text-gray-800 mb-3 flex items-center">
                            <span className="mr-2">📑</span>
                            Documente disponibile ({news.documents.length})
                          </h4>
                          <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            {news.documents.map((doc, docIndex) => (
                              <button
                                key={doc.id}
                                onClick={() => openPreview(doc.url, doc.title)}
                                className="text-left text-xs bg-white hover:bg-blue-50 border border-blue-200 hover:border-blue-300 rounded-lg p-2 transition-all duration-200 flex items-center"
                              >
                                <span className="mr-2">
                                  {doc.filename.endsWith('.pdf') ? '📄' : '📝'}
                                </span>
                                <span className="font-medium text-blue-700 truncate">
                                  {doc.title}
                                </span>
                              </button>
                            ))}
                          </div>
                        </div>
                      )}
                      
                      {/* Footer with Author and Documents Info */}
                      <div className="flex items-center justify-between pt-4 border-t border-gray-100 mt-auto">
                        <div className="flex items-center space-x-3">
                          <div className="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center text-white font-bold text-sm shadow-lg">
                            {news.author.charAt(0)}
                          </div>
                          <div>
                            <div className="text-sm font-semibold text-gray-800">{news.author}</div>
                            <div className="text-xs text-gray-500">Sursă oficială</div>
                          </div>
                        </div>
                        
                        <div className="flex items-center text-blue-600 font-semibold text-sm">
                          <span className="mr-2">📋 Vezi documente</span>
                        </div>
                      </div>
                    </div>
                  </div>
                )}
              </motion.div>
            ))}
          </div>
          
          {/* Enhanced "View all" button */}
          <motion.div 
            className="text-center mt-16"
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6, delay: 0.3 }}
            viewport={{ once: true }}
          >
            <Link 
              to="/noutati"
              className="inline-flex items-center px-8 py-4 bg-gradient-to-r from-primary-500 to-blue-500 text-white rounded-full font-semibold hover:from-primary-600 hover:to-blue-600 transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"
            >
              <span className="mr-3">Vezi toate știrile</span>
              <div className="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center">
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
              </div>
            </Link>
          </motion.div>
        </div>
      </section>
      
      <Contact />

      {/* Document Preview Modal */}
      {previewModal.isOpen && (
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          className="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4"
          onClick={closePreview}
        >
          <motion.div
            initial={{ scale: 0.9, opacity: 0 }}
            animate={{ scale: 1, opacity: 1 }}
            exit={{ scale: 0.9, opacity: 0 }}
            className="bg-white rounded-lg shadow-2xl w-full max-w-6xl h-full max-h-[90vh] flex flex-col"
            onClick={(e) => e.stopPropagation()}
          >
            {/* Modal Header */}
            <div className="flex items-center justify-between p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
              <div>
                <h3 className="text-xl font-bold text-gray-800 mb-1">{previewModal.title}</h3>
                <p className="text-sm text-gray-600">Previzualizare document oficial</p>
              </div>
              <div className="flex items-center space-x-3">
                <button
                  onClick={() => downloadFile(previewModal.url, previewModal.title)}
                  className="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-all duration-200 flex items-center shadow-md hover:shadow-lg"
                >
                  <span className="mr-2">⬇️</span>
                  Descarcă
                </button>
                <button
                  onClick={closePreview}
                  className="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center"
                >
                  <span className="mr-2">✕</span>
                  Închide
                </button>
              </div>
            </div>
            
            {/* Document Viewer */}
            <div className="flex-1 p-6 bg-gray-50">
              <div className="h-full bg-white rounded-lg shadow-inner overflow-hidden">
                <iframe
                  src={previewModal.url}
                  className="w-full h-full border-0"
                  title={previewModal.title}
                  style={{ minHeight: '600px' }}
                />
              </div>
            </div>
          </motion.div>
        </motion.div>
      )}
    </motion.div>
  );
};

export default HomePage;