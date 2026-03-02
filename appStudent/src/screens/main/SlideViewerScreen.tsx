import React, {useState, useRef, useCallback, useEffect} from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  StatusBar,
  Dimensions,
  ScrollView,
  Image,
  Animated,
} from 'react-native';
import {useRoute, useNavigation, RouteProp} from '@react-navigation/native';
import {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {MainStackParamList} from '../../navigation/MainNavigator';
import lessonService, {SlideInfo} from '../../services/lessonService';

type SlideViewerRouteProp = RouteProp<MainStackParamList, 'SlideViewer'>;
type NavigationProp = NativeStackNavigationProp<MainStackParamList>;

const {width: SCREEN_WIDTH} = Dimensions.get('window');

const SlideViewerScreen: React.FC = () => {
  const route = useRoute<SlideViewerRouteProp>();
  const navigation = useNavigation<NavigationProp>();
  const {lessonId, slides, currentSlide} = route.params;

  const [activeIndex, setActiveIndex] = useState(Math.min(currentSlide, slides.length - 1));
  const [maxViewed, setMaxViewed] = useState(currentSlide);
  const scrollRef = useRef<ScrollView>(null);
  const fadeAnim = useRef(new Animated.Value(1)).current;

  const slide = slides[activeIndex];
  const isFirst = activeIndex === 0;
  const isLast = activeIndex === slides.length - 1;

  const animateTransition = useCallback(() => {
    fadeAnim.setValue(0);
    Animated.timing(fadeAnim, {
      toValue: 1,
      duration: 250,
      useNativeDriver: true,
    }).start();
  }, [fadeAnim]);

  const goToSlide = useCallback((index: number) => {
    setActiveIndex(index);
    animateTransition();
    const newMax = Math.max(maxViewed, index + 1);
    setMaxViewed(newMax);

    // Cập nhật progress
    lessonService.updateSlideProgress(lessonId, newMax, slides.length).catch(() => {});
  }, [maxViewed, lessonId, slides.length, animateTransition]);

  const goNext = () => {
    if (!isLast) goToSlide(activeIndex + 1);
  };

  const goPrev = () => {
    if (!isFirst) goToSlide(activeIndex - 1);
  };

  // Scroll content to top when changing slides
  useEffect(() => {
    scrollRef.current?.scrollTo({y: 0, animated: false});
  }, [activeIndex]);

  const handleDone = () => {
    // Đảm bảo cập nhật progress cuối cùng
    lessonService.updateSlideProgress(lessonId, slides.length, slides.length).catch(() => {});
    navigation.goBack();
  };

  const renderBulletContent = (content: string) => {
    const lines = content.split('\n').filter(l => l.trim());
    return lines.map((line, i) => {
      const isBullet = line.trim().startsWith('•') || line.trim().startsWith('-');
      return (
        <Text key={i} style={[styles.slideText, isBullet && styles.bulletText]}>
          {line}
        </Text>
      );
    });
  };

  const getLayoutStyle = () => {
    switch (slide.layout) {
      case 'title':
        return styles.layoutTitle;
      case 'image':
        return styles.layoutImage;
      case 'two_column':
        return styles.layoutTwoCol;
      default:
        return styles.layoutContent;
    }
  };

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#1E1B4B" />

      {/* Top Bar */}
      <View style={styles.topBar}>
        <TouchableOpacity style={styles.closeBtn} onPress={() => navigation.goBack()}>
          <Text style={styles.closeBtnText}>✕</Text>
        </TouchableOpacity>
        <Text style={styles.slideCounter}>
          {activeIndex + 1} / {slides.length}
        </Text>
        <View style={styles.progressTopBar}>
          <View style={[styles.progressTopFill, {width: `${((activeIndex + 1) / slides.length) * 100}%`}]} />
        </View>
      </View>

      {/* Slide Content */}
      <Animated.View style={[styles.slideContainer, getLayoutStyle(), {opacity: fadeAnim}]}>
        <ScrollView
          ref={scrollRef}
          contentContainerStyle={styles.slideScroll}
          showsVerticalScrollIndicator={false}>
          {/* Slide Title */}
          <Text style={[styles.slideTitle, slide.layout === 'title' && styles.slideTitleMain]}>
            {slide.title}
          </Text>

          {/* Image (if layout has image) */}
          {slide.image_url && (slide.layout === 'image' || slide.layout === 'two_column') && (
            <Image
              source={{uri: slide.image_url}}
              style={slide.layout === 'image' ? styles.slideImageFull : styles.slideImageHalf}
              resizeMode="contain"
            />
          )}

          {/* Content */}
          <View style={styles.slideContentArea}>
            {renderBulletContent(slide.content)}
          </View>

          {/* Notes (expandable) */}
          {slide.notes && (
            <View style={styles.notesBox}>
              <Text style={styles.notesLabel}>📝 Ghi chú</Text>
              <Text style={styles.notesText}>{slide.notes}</Text>
            </View>
          )}
        </ScrollView>
      </Animated.View>

      {/* Navigation */}
      <View style={styles.navBar}>
        <TouchableOpacity
          style={[styles.navBtn, isFirst && styles.navBtnDisabled]}
          onPress={goPrev}
          disabled={isFirst}>
          <Text style={[styles.navBtnText, isFirst && styles.navBtnTextDisabled]}>← Trước</Text>
        </TouchableOpacity>

        {/* Slide dots */}
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.dots}>
          {slides.map((_, i) => (
            <TouchableOpacity key={i} onPress={() => goToSlide(i)}>
              <View style={[
                styles.dot,
                i === activeIndex && styles.dotActive,
                i < maxViewed && styles.dotViewed,
              ]} />
            </TouchableOpacity>
          ))}
        </ScrollView>

        {isLast ? (
          <TouchableOpacity style={[styles.navBtn, styles.navBtnDone]} onPress={handleDone}>
            <Text style={styles.navBtnDoneText}>Hoàn tất ✓</Text>
          </TouchableOpacity>
        ) : (
          <TouchableOpacity style={styles.navBtn} onPress={goNext}>
            <Text style={styles.navBtnText}>Tiếp →</Text>
          </TouchableOpacity>
        )}
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {flex: 1, backgroundColor: '#1E1B4B'},

  // Top Bar
  topBar: {
    flexDirection: 'row', alignItems: 'center',
    paddingHorizontal: 16, paddingTop: 10, paddingBottom: 8,
    gap: 12,
  },
  closeBtn: {
    width: 32, height: 32, borderRadius: 16,
    backgroundColor: 'rgba(255,255,255,0.15)',
    justifyContent: 'center', alignItems: 'center',
  },
  closeBtnText: {color: '#FFF', fontSize: 16, fontWeight: '600'},
  slideCounter: {color: 'rgba(255,255,255,0.7)', fontSize: 13, fontWeight: '600'},
  progressTopBar: {
    flex: 1, height: 4, backgroundColor: 'rgba(255,255,255,0.15)', borderRadius: 2, overflow: 'hidden',
  },
  progressTopFill: {height: '100%', backgroundColor: '#818CF8', borderRadius: 2},

  // Slide
  slideContainer: {
    flex: 1, margin: 12, borderRadius: 20, overflow: 'hidden',
  },
  layoutTitle: {backgroundColor: '#4F46E5'},
  layoutContent: {backgroundColor: '#FFFFFF'},
  layoutImage: {backgroundColor: '#FFFFFF'},
  layoutTwoCol: {backgroundColor: '#FFFFFF'},

  slideScroll: {padding: 24, paddingBottom: 40},

  slideTitle: {
    fontSize: 22, fontWeight: '800', color: '#1F2937',
    marginBottom: 16, lineHeight: 30,
  },
  slideTitleMain: {
    fontSize: 28, color: '#FFFFFF', textAlign: 'center', marginTop: 60, marginBottom: 24,
  },

  slideContentArea: {gap: 6},
  slideText: {fontSize: 16, color: '#374151', lineHeight: 26},
  bulletText: {paddingLeft: 8},

  slideImageFull: {
    width: '100%', height: 220, borderRadius: 12,
    marginBottom: 16, backgroundColor: '#F3F4F6',
  },
  slideImageHalf: {
    width: '100%', height: 160, borderRadius: 12,
    marginBottom: 16, backgroundColor: '#F3F4F6',
  },

  notesBox: {
    marginTop: 20, padding: 14, backgroundColor: '#F3F4F6',
    borderRadius: 12, borderLeftWidth: 3, borderLeftColor: '#818CF8',
  },
  notesLabel: {fontSize: 13, fontWeight: '700', color: '#4F46E5', marginBottom: 6},
  notesText: {fontSize: 13, color: '#6B7280', lineHeight: 20},

  // Navigation
  navBar: {
    flexDirection: 'row', alignItems: 'center',
    paddingHorizontal: 16, paddingVertical: 12, gap: 10,
  },
  navBtn: {
    paddingHorizontal: 18, paddingVertical: 10, borderRadius: 10,
    backgroundColor: 'rgba(255,255,255,0.15)',
  },
  navBtnDisabled: {opacity: 0.3},
  navBtnText: {color: '#FFF', fontSize: 14, fontWeight: '600'},
  navBtnTextDisabled: {color: 'rgba(255,255,255,0.4)'},
  navBtnDone: {backgroundColor: '#10B981'},
  navBtnDoneText: {color: '#FFF', fontSize: 14, fontWeight: '700'},

  dots: {flexDirection: 'row', alignItems: 'center', gap: 6, paddingHorizontal: 4},
  dot: {
    width: 8, height: 8, borderRadius: 4, backgroundColor: 'rgba(255,255,255,0.2)',
  },
  dotActive: {width: 20, backgroundColor: '#818CF8'},
  dotViewed: {backgroundColor: 'rgba(255,255,255,0.5)'},
});

export default SlideViewerScreen;
