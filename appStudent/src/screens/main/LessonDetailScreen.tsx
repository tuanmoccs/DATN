import React, {useState, useEffect, useCallback} from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  StatusBar,
  RefreshControl,
  Image,
} from 'react-native';
import {useRoute, useNavigation, RouteProp} from '@react-navigation/native';
import {NativeStackNavigationProp} from '@react-navigation/native-stack';
import lessonService, {LessonDetail, QuizOverview} from '../../services/lessonService';
import {MainStackParamList} from '../../navigation/MainNavigator';

type LessonDetailRouteProp = RouteProp<MainStackParamList, 'LessonDetail'>;
type NavigationProp = NativeStackNavigationProp<MainStackParamList>;

const LessonDetailScreen: React.FC = () => {
  const route = useRoute<LessonDetailRouteProp>();
  const navigation = useNavigation<NavigationProp>();
  const {lessonId} = route.params;

  const [lesson, setLesson] = useState<LessonDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchLesson = useCallback(async (isRefresh = false) => {
    if (isRefresh) setRefreshing(true);
    else setLoading(true);
    try {
      const response = await lessonService.getLessonDetail(lessonId);
      if (response.success) {
        setLesson(response.data);
      }
    } catch (error) {
      console.error('Error fetching lesson:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [lessonId]);

  useEffect(() => {
    fetchLesson();
  }, [fetchLesson]);

  // Refresh when coming back from quiz
  useEffect(() => {
    const unsubscribe = navigation.addListener('focus', () => {
      if (lesson) fetchLesson(true);
    });
    return unsubscribe;
  }, [navigation, lesson, fetchLesson]);

  const handleStartSlides = () => {
    if (!lesson || lesson.slides.length === 0) return;
    navigation.navigate('SlideViewer', {
      lessonId: lesson.id,
      slides: lesson.slides,
      currentSlide: lesson.progress.slides_viewed || 0,
    });
  };

  const handleStartQuiz = (quiz: QuizOverview) => {
    if (!lesson?.progress.slides_completed) return;
    navigation.navigate('QuizScreen', {
      quizId: quiz.id,
      lessonId: lesson.id,
    });
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#2563EB" />
      </View>
    );
  }

  if (!lesson) {
    return (
      <View style={styles.errorContainer}>
        <Text style={styles.errorIcon}>😔</Text>
        <Text style={styles.errorText}>Không thể tải bài học</Text>
        <TouchableOpacity style={styles.retryBtn} onPress={() => fetchLesson()}>
          <Text style={styles.retryBtnText}>Thử lại</Text>
        </TouchableOpacity>
      </View>
    );
  }

  const progressPercent = lesson.progress.total_slides > 0
    ? Math.round((lesson.progress.slides_viewed / lesson.progress.total_slides) * 100)
    : 0;

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#4F46E5" />

      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
          <Text style={styles.backIcon}>←</Text>
        </TouchableOpacity>
        <View style={styles.headerContent}>
          <Text style={styles.headerTitle} numberOfLines={1}>{lesson.title}</Text>
          {lesson.progress.lesson_completed && (
            <View style={styles.completedBadge}>
              <Text style={styles.completedBadgeText}>✓ Hoàn thành</Text>
            </View>
          )}
        </View>
      </View>

      <ScrollView
        style={styles.content}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={() => fetchLesson(true)} colors={['#4F46E5']} />
        }
        showsVerticalScrollIndicator={false}>

        {/* Progress Card */}
        <View style={styles.progressCard}>
          <Text style={styles.progressTitle}>Tiến trình học</Text>
          <View style={styles.progressBarBg}>
            <View style={[styles.progressBarFill, {width: `${progressPercent}%`}]} />
          </View>
          <View style={styles.progressSteps}>
            <View style={styles.progressStep}>
              <View style={[styles.stepDot, lesson.progress.slides_completed && styles.stepDotDone]}>
                <Text style={styles.stepDotText}>{lesson.progress.slides_completed ? '✓' : '1'}</Text>
              </View>
              <Text style={styles.stepLabel}>Xem slide</Text>
            </View>
            <View style={styles.progressStepLine} />
            <View style={styles.progressStep}>
              <View style={[styles.stepDot, lesson.progress.quiz_completed && styles.stepDotDone]}>
                <Text style={styles.stepDotText}>{lesson.progress.quiz_completed ? '✓' : '2'}</Text>
              </View>
              <Text style={styles.stepLabel}>Làm quiz</Text>
            </View>
            <View style={styles.progressStepLine} />
            <View style={styles.progressStep}>
              <View style={[styles.stepDot, lesson.progress.lesson_completed && styles.stepDotDone]}>
                <Text style={styles.stepDotText}>{lesson.progress.lesson_completed ? '✓' : '3'}</Text>
              </View>
              <Text style={styles.stepLabel}>Hoàn thành</Text>
            </View>
          </View>
        </View>

        {/* Description */}
        {lesson.description && (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>📋 Mô tả</Text>
            <Text style={styles.cardText}>{lesson.description}</Text>
          </View>
        )}

        {/* Objectives */}
        {lesson.objectives && (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>🎯 Mục tiêu</Text>
            <Text style={styles.cardText}>{lesson.objectives}</Text>
          </View>
        )}

        {/* Slides Section */}
        <View style={styles.card}>
          <Text style={styles.cardTitle}>📑 Slide bài giảng</Text>
          {lesson.slides.length > 0 ? (
            <>
              <Text style={styles.slideInfo}>
                {lesson.progress.slides_viewed} / {lesson.progress.total_slides} slide đã xem
              </Text>

              {/* Preview first slide image */}
              {lesson.slides[0]?.image_url && (
                <Image
                  source={{uri: lesson.slides[0].image_url}}
                  style={styles.slidePreview}
                  resizeMode="cover"
                />
              )}

              <TouchableOpacity
                style={[
                  styles.primaryBtn,
                  lesson.progress.slides_completed && styles.primaryBtnDone,
                ]}
                onPress={handleStartSlides}>
                <Text style={styles.primaryBtnText}>
                  {lesson.progress.slides_completed
                    ? '✓ Xem lại slide'
                    : lesson.progress.slides_viewed > 0
                    ? '▶ Tiếp tục xem slide'
                    : '▶ Bắt đầu xem slide'}
                </Text>
              </TouchableOpacity>
            </>
          ) : (
            <Text style={styles.emptyText}>Chưa có slide cho bài học này</Text>
          )}
        </View>

        {/* Quiz Section */}
        <View style={styles.card}>
          <Text style={styles.cardTitle}>📝 Câu hỏi kiểm tra</Text>
          {!lesson.progress.slides_completed && lesson.quizzes.length > 0 && (
            <View style={styles.lockBanner}>
              <Text style={styles.lockIcon}>🔒</Text>
              <Text style={styles.lockText}>Xem hết slide để mở câu hỏi</Text>
            </View>
          )}
          {lesson.quizzes.length > 0 ? (
            lesson.quizzes.map(quiz => (
              <View key={quiz.id} style={styles.quizCard}>
                <View style={styles.quizHeader}>
                  <Text style={styles.quizTitle}>{quiz.title}</Text>
                  {quiz.latest_attempt && (
                    <View style={[
                      styles.scoreBadge,
                      (quiz.latest_attempt.percentage ?? 0) >= 50 ? styles.scoreBadgePass : styles.scoreBadgeFail,
                    ]}>
                      <Text style={styles.scoreBadgeText}>
                        {Math.round(quiz.latest_attempt.percentage)}%
                      </Text>
                    </View>
                  )}
                </View>

                <View style={styles.quizMeta}>
                  <Text style={styles.quizMetaText}>❓ {quiz.question_count} câu hỏi</Text>
                  <Text style={styles.quizMetaText}>⭐ {quiz.total_points} điểm</Text>
                  {quiz.time_limit && (
                    <Text style={styles.quizMetaText}>⏱ {quiz.time_limit} phút</Text>
                  )}
                </View>

                {quiz.attempt_count > 0 && (
                  <Text style={styles.quizAttemptInfo}>
                    Đã làm {quiz.attempt_count}{quiz.max_attempts ? `/${quiz.max_attempts}` : ''} lần
                    {quiz.best_score !== null && ` • Điểm cao nhất: ${Math.round(quiz.best_score)}%`}
                  </Text>
                )}

                <TouchableOpacity
                  style={[
                    styles.quizBtn,
                    !lesson.progress.slides_completed && styles.quizBtnDisabled,
                    !quiz.can_attempt && styles.quizBtnDisabled,
                  ]}
                  onPress={() => handleStartQuiz(quiz)}
                  disabled={!lesson.progress.slides_completed || !quiz.can_attempt}>
                  <Text style={[
                    styles.quizBtnText,
                    (!lesson.progress.slides_completed || !quiz.can_attempt) && styles.quizBtnTextDisabled,
                  ]}>
                    {!quiz.can_attempt
                      ? 'Đã hết lượt làm'
                      : quiz.attempt_count > 0
                      ? 'Làm lại'
                      : 'Bắt đầu làm bài'}
                  </Text>
                </TouchableOpacity>
              </View>
            ))
          ) : (
            <Text style={styles.emptyText}>Chưa có câu hỏi kiểm tra</Text>
          )}
        </View>

        <View style={{height: 32}} />
      </ScrollView>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {flex: 1, backgroundColor: '#F3F4F6'},
  loadingContainer: {flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#F3F4F6'},
  errorContainer: {flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#F3F4F6', padding: 40},
  errorIcon: {fontSize: 48, marginBottom: 16},
  errorText: {fontSize: 16, color: '#6B7280', marginBottom: 20},
  retryBtn: {backgroundColor: '#4F46E5', paddingHorizontal: 24, paddingVertical: 12, borderRadius: 10},
  retryBtnText: {color: '#FFF', fontWeight: '600'},

  // Header
  header: {
    backgroundColor: '#4F46E5',
    flexDirection: 'row',
    alignItems: 'center',
    paddingTop: 12,
    paddingBottom: 16,
    paddingHorizontal: 16,
  },
  backBtn: {
    width: 36, height: 36, borderRadius: 18,
    backgroundColor: 'rgba(255,255,255,0.2)',
    justifyContent: 'center', alignItems: 'center', marginRight: 12,
  },
  backIcon: {fontSize: 20, color: '#FFF', fontWeight: '700'},
  headerContent: {flex: 1, flexDirection: 'row', alignItems: 'center', gap: 10},
  headerTitle: {fontSize: 18, fontWeight: '700', color: '#FFF', flex: 1},
  completedBadge: {backgroundColor: '#10B981', paddingHorizontal: 10, paddingVertical: 4, borderRadius: 12},
  completedBadgeText: {color: '#FFF', fontSize: 12, fontWeight: '600'},

  content: {flex: 1},

  // Progress Card
  progressCard: {
    backgroundColor: '#FFF', margin: 16, borderRadius: 16, padding: 20,
    shadowColor: '#000', shadowOffset: {width: 0, height: 2}, shadowOpacity: 0.06, shadowRadius: 8, elevation: 2,
  },
  progressTitle: {fontSize: 16, fontWeight: '700', color: '#1F2937', marginBottom: 12},
  progressBarBg: {height: 8, backgroundColor: '#E5E7EB', borderRadius: 4, overflow: 'hidden'},
  progressBarFill: {height: '100%', backgroundColor: '#4F46E5', borderRadius: 4},
  progressSteps: {flexDirection: 'row', alignItems: 'center', justifyContent: 'center', marginTop: 16},
  progressStep: {alignItems: 'center'},
  progressStepLine: {height: 2, width: 40, backgroundColor: '#E5E7EB', marginHorizontal: 4},
  stepDot: {
    width: 32, height: 32, borderRadius: 16,
    backgroundColor: '#E5E7EB', justifyContent: 'center', alignItems: 'center', marginBottom: 4,
  },
  stepDotDone: {backgroundColor: '#10B981'},
  stepDotText: {fontSize: 13, fontWeight: '700', color: '#FFF'},
  stepLabel: {fontSize: 11, color: '#6B7280', fontWeight: '500'},

  // Card
  card: {
    backgroundColor: '#FFF', marginHorizontal: 16, marginBottom: 12, borderRadius: 16, padding: 16,
    shadowColor: '#000', shadowOffset: {width: 0, height: 1}, shadowOpacity: 0.04, shadowRadius: 4, elevation: 1,
  },
  cardTitle: {fontSize: 16, fontWeight: '700', color: '#1F2937', marginBottom: 10},
  cardText: {fontSize: 14, color: '#4B5563', lineHeight: 22},
  slideInfo: {fontSize: 13, color: '#6B7280', marginBottom: 12},
  slidePreview: {width: '100%', height: 160, borderRadius: 10, marginBottom: 12, backgroundColor: '#F3F4F6'},
  emptyText: {fontSize: 14, color: '#9CA3AF', fontStyle: 'italic'},

  // Buttons
  primaryBtn: {
    backgroundColor: '#4F46E5', borderRadius: 12, paddingVertical: 14, alignItems: 'center', marginTop: 4,
  },
  primaryBtnDone: {backgroundColor: '#10B981'},
  primaryBtnText: {color: '#FFF', fontSize: 15, fontWeight: '600'},

  // Lock
  lockBanner: {
    flexDirection: 'row', alignItems: 'center', backgroundColor: '#FEF3C7',
    padding: 10, borderRadius: 10, marginBottom: 12, gap: 8,
  },
  lockIcon: {fontSize: 18},
  lockText: {fontSize: 13, color: '#92400E', fontWeight: '500'},

  // Quiz Card
  quizCard: {
    backgroundColor: '#F9FAFB', borderRadius: 12, padding: 14, marginBottom: 10,
    borderWidth: 1, borderColor: '#E5E7EB',
  },
  quizHeader: {flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8},
  quizTitle: {fontSize: 15, fontWeight: '600', color: '#1F2937', flex: 1},
  scoreBadge: {paddingHorizontal: 10, paddingVertical: 4, borderRadius: 10},
  scoreBadgePass: {backgroundColor: '#D1FAE5'},
  scoreBadgeFail: {backgroundColor: '#FEE2E2'},
  scoreBadgeText: {fontSize: 13, fontWeight: '700'},
  quizMeta: {flexDirection: 'row', gap: 14, marginBottom: 6},
  quizMetaText: {fontSize: 13, color: '#6B7280'},
  quizAttemptInfo: {fontSize: 12, color: '#9CA3AF', marginBottom: 8},
  quizBtn: {
    backgroundColor: '#4F46E5', borderRadius: 10, paddingVertical: 12, alignItems: 'center',
  },
  quizBtnDisabled: {backgroundColor: '#D1D5DB'},
  quizBtnText: {color: '#FFF', fontSize: 14, fontWeight: '600'},
  quizBtnTextDisabled: {color: '#9CA3AF'},
});

export default LessonDetailScreen;
