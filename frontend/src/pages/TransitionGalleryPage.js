import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { motion } from 'framer-motion';
import {
  FaArrowLeft, FaCalendarAlt, FaUser, FaClock, FaExpandArrowsAlt,
  FaTimes, FaChevronLeft, FaChevronRight, FaDownload
} from 'react-icons/fa';
import AOS from "aos";
import "aos/dist/aos.css";
import { transitionNewsData } from '../data/newsData';

const TransitionGalleryPage = () => {
  const [selectedImage, setSelectedImage] = useState(null);
  const [currentImageIndex, setCurrentImageIndex] = useState(0);
  const navigate = useNavigate();

  // Initialize AOS
  useEffect(() => {
    AOS.init({
      duration: 1000,
      once: true,
    });
  }, []);

  // Fetch complete news data from backend
  const [newsData, setNewsData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const loadData = async () => {
      try {
        // Use static data instead of API call
        setNewsData(transitionNewsData);
      } catch (error) {
        console.error('Error loading data:', error);
      } finally {
        setLoading(false);
      }
    };

    loadData();
  }, []);

  const openLightbox = (index) => {
    setCurrentImageIndex(index);
    setSelectedImage(newsData.images[index]);
  };

  const closeLightbox = () => {
    setSelectedImage(null);
  };

  const nextImage = () => {
    const nextIndex = (currentImageIndex + 1) % newsData.images.length;
    setCurrentImageIndex(nextIndex);
    setSelectedImage(newsData.images[nextIndex]);
  };

  const prevImage = () => {
    const prevIndex = currentImageIndex === 0 ? newsData.images.length - 1 : currentImageIndex - 1;
    setCurrentImageIndex(prevIndex);
    setSelectedImage(newsData.images[prevIndex]);
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <div className="w-20 h-20 rounded-full border-4 border-gray-200 border-t-primary-500 animate-spin mx-auto mb-6"></div>
          <h2 className="text-xl font-semibold text-gray-700">Se încarcă galeria...</h2>
        </div>
      </div>
    );
  }

  if (!newsData) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <h2 className="text-xl font-semibold text-gray-700 mb-4">Nu s-au găsit date</h2>
          <Link 
            to="/ultimele-stiri"
            className="inline-flex items-center px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
          >
            <FaArrowLeft className="mr-2" />
            Înapoi la Ultimele Știri
          </Link>
        </div>
      </div>
    );
  }

  return (
    <motion.div
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      exit={{ opacity: 0 }}
      transition={{ duration: 0.5 }}
      className="transition-gallery-page min-h-screen bg-gray-50"
    >
      {/* Hero Section */}
      <div className="bg-gradient-to-r from-green-800 via-green-700 to-green-600 text-white">
        <div className="container mx-auto px-4 py-16">
          {/* Navigation */}
          <div className="mb-8">
            <Link 
              to="/ultimele-stiri"
              className="inline-flex items-center text-green-100 hover:text-white transition-colors"
            >
              <FaArrowLeft className="mr-2" />
              Înapoi la Ultimele Știri
            </Link>
          </div>

          {/* Title and Info */}
          <div className="max-w-4xl">
            <h1 className="text-4xl md:text-5xl font-bold mb-6 leading-tight">
              {newsData.title}
            </h1>
            
            <div className="flex flex-wrap gap-4 mb-6 text-green-100">
              <div className="flex items-center">
                <FaCalendarAlt className="mr-2" />
                <span>{newsData.date}</span>
              </div>
              <div className="flex items-center">
                <FaUser className="mr-2" />
                <span>{newsData.author}</span>
              </div>
              <div className="flex items-center">
                <FaClock className="mr-2" />
                <span>{newsData.readTime}</span>
              </div>
            </div>

            <p className="text-xl text-green-100 leading-relaxed">
              {newsData.summary}
            </p>
          </div>
        </div>
      </div>

      {/* Content Section */}
      <div className="container mx-auto px-4 py-16">
        {/* Article Content */}
        <div className="max-w-4xl mx-auto mb-16">
          <div 
            className="prose prose-lg max-w-none"
            data-aos="fade-up"
          >
            <div className="bg-white rounded-xl p-8 shadow-sm border border-gray-100">
              <h2 className="text-2xl font-bold text-gray-900 mb-6">Despre Eveniment</h2>
              <div className="text-gray-700 leading-relaxed whitespace-pre-line">
                {newsData.fullContent}
              </div>
            </div>
          </div>
        </div>

        {/* Gallery Section */}
        <div className="max-w-6xl mx-auto">
          <h2 
            className="text-3xl font-bold text-center text-gray-900 mb-12"
            data-aos="fade-up"
          >
            Galeria Foto - Conferința Națională
          </h2>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {newsData.images.map((image, index) => (
              <motion.div
                key={index}
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.5, delay: index * 0.1 }}
                viewport={{ once: true }}
                className="group cursor-pointer"
                onClick={() => openLightbox(index)}
              >
                <div className="relative overflow-hidden rounded-xl shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2">
                  <img 
                    src={image} 
                    alt={`Conferința Tranziția Verde ${index + 1}`}
                    className="w-full h-64 object-cover transition-transform duration-500 group-hover:scale-110"
                  />
                  <div className="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all duration-300 flex items-center justify-center">
                    <FaExpandArrowsAlt className="text-white text-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
                  </div>
                  <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-4">
                    <p className="text-white text-sm font-medium">
                      Imagine {index + 1} din {newsData.images.length}
                    </p>
                  </div>
                </div>
              </motion.div>
            ))}
          </div>
        </div>

        {/* Call to Action */}
        <div className="max-w-4xl mx-auto mt-16 text-center">
          <div 
            className="bg-gradient-to-r from-green-600 to-green-700 text-white rounded-xl p-8"
            data-aos="fade-up"
          >
            <h3 className="text-2xl font-bold mb-4">Alătură-te Tranziției Verde!</h3>
            <p className="text-green-100 mb-6 text-lg">
              Află mai multe despre inițiativele de sustenabilitate și cum poți contribui la dezvoltarea unei economii verzi în Moldova.
            </p>
            <div className="flex flex-wrap justify-center gap-4">
              <Link 
                to="/contact"
                className="bg-white text-green-600 px-6 py-3 rounded-lg font-semibold hover:bg-green-50 transition-colors"
              >
                Contactează-ne
              </Link>
              <Link 
                to="/evenimente"
                className="border-2 border-white text-white px-6 py-3 rounded-lg font-semibold hover:bg-white hover:text-green-600 transition-colors"
              >
                Vezi Toate Evenimentele
              </Link>
            </div>
          </div>
        </div>
      </div>

      {/* Lightbox Modal */}
      {selectedImage && (
        <div className="fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center p-4">
          <div className="relative max-w-4xl w-full">
            {/* Close Button */}
            <button
              onClick={closeLightbox}
              className="absolute top-4 right-4 text-white text-2xl hover:text-gray-300 transition-colors z-10"
            >
              <FaTimes />
            </button>

            {/* Navigation Buttons */}
            <button
              onClick={prevImage}
              className="absolute left-4 top-1/2 transform -translate-y-1/2 text-white text-2xl hover:text-gray-300 transition-colors z-10"
            >
              <FaChevronLeft />
            </button>

            <button
              onClick={nextImage}
              className="absolute right-4 top-1/2 transform -translate-y-1/2 text-white text-2xl hover:text-gray-300 transition-colors z-10"
            >
              <FaChevronRight />
            </button>

            {/* Image */}
            <div className="flex items-center justify-center">
              <img 
                src={selectedImage} 
                alt={`Conferința Tranziția Verde ${currentImageIndex + 1}`}
                className="max-w-full max-h-screen object-contain"
              />
            </div>

            {/* Image Counter */}
            <div className="absolute bottom-4 left-1/2 transform -translate-x-1/2 text-white bg-black bg-opacity-50 px-4 py-2 rounded-lg">
              {currentImageIndex + 1} din {newsData.images.length}
            </div>
          </div>
        </div>
      )}
    </motion.div>
  );
};

export default TransitionGalleryPage;

