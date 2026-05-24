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
  Modal,
  TextInput,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import {useRoute, useNavigation, RouteProp} from '@react-navigation/native';
import {NativeStackNavigationProp} from '@react-navigation/native-stack';
import lessonService, {
  LessonChatMessage,
  LessonDetail,
  QuizOverview,
} from '../../services/lessonService';
import {MainStackParamList} from '../../navigation/MainNavigator';
import {getImageUrl} from '../../config/api';

type LessonDetailRouteProp = RouteProp<MainStackParamList, 'LessonDetail'>;
type NavigationProp = NativeStackNavigationProp<MainStackParamList>;

const LessonDetailScreen: React.FC = () => {
  const route = useRoute<LessonDetailRouteProp>();
  const navigation = useNavigation<NavigationProp>();
  const {lessonId} = route.params;

  const [lesson, setLesson] = useState<LessonDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [chatVisible, setChatVisible] = useState(false);
  const [chatInput, setChatInput] = useState('');
  const [chatSending, setChatSending] = useState(false);
  const [chatMessages, setChatMessages] = useState<LessonChatMessage[]>([
    {
      role: 'assistant',
      content:
        'Bạn có thể hỏi về nội dung bài học hoặc cách suy luận khi làm quiz. Mình sẽ gợi ý và giải thích, nhưng không đưa đáp án đúng.',
    },
  ]);

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

  const handleSendChat = async () => {
    const text = chatInput.trim();
    if (!lesson || !text || chatSending) return;

    const history = chatMessages;
    const nextMessages: LessonChatMessage[] = [
      ...chatMessages,
      {role: 'user', content: text},
    ];

    setChatMessages(nextMessages);
    setChatInput('');
    setChatSending(true);

    try {
      const response = await lessonService.askAssistant(lesson.id, text, history);
      setChatMessages(current => [
        ...current,
        {
          role: 'assistant',
          content:
            response.data.answer ||
            'Mình chưa tìm thấy đủ thông tin trong bài học để trả lời câu này.',
        },
      ]);
    } catch (error: any) {
      setChatMessages(current => [
        ...current,
        {
          role: 'assistant',
          content:
            error?.response?.data?.message ||
            'Hiện chưa thể gửi câu hỏi. Bạn thử lại sau nhé.',
        },
      ]);
    } finally {
      setChatSending(false);
    }
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
  const previewSlideImageUrl = getImageUrl(lesson.slides.find(slide => slide.image_url)?.image_url);

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#0D47A1" />

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
          <RefreshControl refreshing={refreshing} onRefresh={() => fetchLesson(true)} colors={['#0D47A1']} />
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

              {/* Preview first available slide image */}
              {previewSlideImageUrl && (
                <Image
                  source={{uri: previewSlideImageUrl}}
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
          <View style={styles.quizSectionHeader}>
            <Text style={styles.cardTitle}>📝 Câu hỏi kiểm tra</Text>
            <TouchableOpacity
              style={styles.assistantBtn}
              onPress={() => setChatVisible(true)}>
              <Text style={styles.assistantBtnText}>AI hỏi đáp</Text>
            </TouchableOpacity>
          </View>
          <Text style={styles.assistantHint}>
            Hỏi gợi ý về bài học và quiz, AI sẽ không đưa đáp án đúng.
          </Text>
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

      <Modal
        visible={chatVisible}
        animationType="slide"
        transparent
        onRequestClose={() => setChatVisible(false)}>
        <KeyboardAvoidingView
          behavior={Platform.OS === 'ios' ? 'padding' : undefined}
          style={styles.chatOverlay}>
          <View style={styles.chatPanel}>
            <View style={styles.chatHeader}>
              <View>
                <Text style={styles.chatTitle}>AI hỏi đáp</Text>
                <Text style={styles.chatSubtitle}>Gợi ý học tập, không lộ đáp án quiz</Text>
              </View>
              <TouchableOpacity
                style={styles.chatCloseBtn}
                onPress={() => setChatVisible(false)}>
                <Text style={styles.chatCloseText}>×</Text>
              </TouchableOpacity>
            </View>

            <ScrollView style={styles.chatMessages} showsVerticalScrollIndicator={false}>
              {chatMessages.map((message, index) => (
                <View
                  key={`${message.role}-${index}`}
                  style={[
                    styles.chatBubble,
                    message.role === 'user'
                      ? styles.chatBubbleUser
                      : styles.chatBubbleAssistant,
                  ]}>
                  <Text
                    style={[
                      styles.chatBubbleText,
                      message.role === 'user' && styles.chatBubbleTextUser,
                    ]}>
                    {message.content}
                  </Text>
                </View>
              ))}
              {chatSending && (
                <View style={[styles.chatBubble, styles.chatBubbleAssistant]}>
                  <ActivityIndicator size="small" color="#0D47A1" />
                </View>
              )}
            </ScrollView>

            <View style={styles.chatInputRow}>
              <TextInput
                style={styles.chatInput}
                value={chatInput}
                onChangeText={setChatInput}
                placeholder="Nhập câu hỏi về bài học hoặc quiz..."
                placeholderTextColor="#94A3B8"
                multiline
              />
              <TouchableOpacity
                style={[
                  styles.chatSendBtn,
                  (!chatInput.trim() || chatSending) && styles.chatSendBtnDisabled,
                ]}
                disabled={!chatInput.trim() || chatSending}
                onPress={handleSendChat}>
                <Text style={styles.chatSendText}>Gửi</Text>
              </TouchableOpacity>
            </View>
          </View>
        </KeyboardAvoidingView>
      </Modal>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {flex: 1, backgroundColor: '#F0F4F8'},
  loadingContainer: {flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#F0F4F8'},
  errorContainer: {flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#F0F4F8', padding: 40},
  errorIcon: {fontSize: 48, marginBottom: 16},
  errorText: {fontSize: 16, color: '#64748B', marginBottom: 20},
  retryBtn: {backgroundColor: '#0D47A1', paddingHorizontal: 24, paddingVertical: 12, borderRadius: 6},
  retryBtnText: {color: '#FFF', fontWeight: '600'},

  header: {
    backgroundColor: '#0D47A1',
    flexDirection: 'row',
    alignItems: 'center',
    paddingTop: (StatusBar.currentHeight || 0) + 12,
    paddingBottom: 16,
    paddingHorizontal: 16,
  },
  backBtn: {
    width: 36, height: 36, borderRadius: 6,
    backgroundColor: 'rgba(255,255,255,0.15)',
    justifyContent: 'center', alignItems: 'center', marginRight: 12,
  },
  backIcon: {fontSize: 20, color: '#FFF', fontWeight: '700'},
  headerContent: {flex: 1, flexDirection: 'row', alignItems: 'center', gap: 10},
  headerTitle: {fontSize: 17, fontWeight: '700', color: '#FFF', flex: 1},
  completedBadge: {backgroundColor: '#10B981', paddingHorizontal: 8, paddingVertical: 3, borderRadius: 4},
  completedBadgeText: {color: '#FFF', fontSize: 11, fontWeight: '600'},

  content: {flex: 1},

  progressCard: {
    backgroundColor: '#FFF', margin: 16, borderRadius: 8, padding: 18,
    shadowColor: '#0D47A1', shadowOffset: {width: 0, height: 2}, shadowOpacity: 0.06, shadowRadius: 8, elevation: 2,
  },
  progressTitle: {fontSize: 15, fontWeight: '700', color: '#0F172A', marginBottom: 12},
  progressBarBg: {height: 6, backgroundColor: '#E2E8F0', borderRadius: 3, overflow: 'hidden'},
  progressBarFill: {height: '100%', backgroundColor: '#0D47A1', borderRadius: 3},
  progressSteps: {flexDirection: 'row', alignItems: 'center', justifyContent: 'center', marginTop: 16},
  progressStep: {alignItems: 'center'},
  progressStepLine: {height: 2, width: 40, backgroundColor: '#E2E8F0', marginHorizontal: 4},
  stepDot: {
    width: 30, height: 30, borderRadius: 6,
    backgroundColor: '#E2E8F0', justifyContent: 'center', alignItems: 'center', marginBottom: 4,
  },
  stepDotDone: {backgroundColor: '#10B981'},
  stepDotText: {fontSize: 12, fontWeight: '700', color: '#FFF'},
  stepLabel: {fontSize: 10, color: '#64748B', fontWeight: '500'},

  card: {
    backgroundColor: '#FFF', marginHorizontal: 16, marginBottom: 10, borderRadius: 8, padding: 16,
    shadowColor: '#0D47A1', shadowOffset: {width: 0, height: 1}, shadowOpacity: 0.04, shadowRadius: 4, elevation: 1,
  },
  cardTitle: {fontSize: 15, fontWeight: '700', color: '#0F172A', marginBottom: 10},
  cardText: {fontSize: 13, color: '#475569', lineHeight: 20},
  slideInfo: {fontSize: 12, color: '#64748B', marginBottom: 10},
  slidePreview: {width: '100%', height: 160, borderRadius: 6, marginBottom: 12, backgroundColor: '#E2E8F0'},
  emptyText: {fontSize: 13, color: '#94A3B8', fontStyle: 'italic'},

  primaryBtn: {
    backgroundColor: '#0D47A1', borderRadius: 6, paddingVertical: 13, alignItems: 'center', marginTop: 4,
  },
  primaryBtnDone: {backgroundColor: '#10B981'},
  primaryBtnText: {color: '#FFF', fontSize: 14, fontWeight: '600'},

  lockBanner: {
    flexDirection: 'row', alignItems: 'center', backgroundColor: '#FEF3C7',
    padding: 10, borderRadius: 4, marginBottom: 12, gap: 8,
    borderLeftWidth: 3, borderLeftColor: '#F59E0B',
  },
  lockIcon: {fontSize: 16},
  lockText: {fontSize: 12, color: '#92400E', fontWeight: '500'},

  quizSectionHeader: {flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 10},
  assistantBtn: {
    backgroundColor: '#E3F2FD',
    borderRadius: 6,
    paddingHorizontal: 10,
    paddingVertical: 7,
  },
  assistantBtnText: {fontSize: 12, color: '#0D47A1', fontWeight: '700'},
  assistantHint: {fontSize: 12, color: '#64748B', lineHeight: 17, marginTop: -4, marginBottom: 12},

  quizCard: {
    backgroundColor: '#F8FAFC', borderRadius: 6, padding: 14, marginBottom: 10,
    borderWidth: 1, borderColor: '#E2E8F0',
  },
  quizHeader: {flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8},
  quizTitle: {fontSize: 14, fontWeight: '600', color: '#0F172A', flex: 1},
  scoreBadge: {paddingHorizontal: 8, paddingVertical: 3, borderRadius: 4},
  scoreBadgePass: {backgroundColor: '#ECFDF5'},
  scoreBadgeFail: {backgroundColor: '#FEF2F2'},
  scoreBadgeText: {fontSize: 12, fontWeight: '700'},
  quizMeta: {flexDirection: 'row', gap: 12, marginBottom: 6},
  quizMetaText: {fontSize: 12, color: '#64748B'},
  quizAttemptInfo: {fontSize: 11, color: '#94A3B8', marginBottom: 8},
  quizBtn: {
    backgroundColor: '#0D47A1', borderRadius: 6, paddingVertical: 11, alignItems: 'center',
  },
  quizBtnDisabled: {backgroundColor: '#CBD5E1'},
  quizBtnText: {color: '#FFF', fontSize: 13, fontWeight: '600'},
  quizBtnTextDisabled: {color: '#94A3B8'},
  chatOverlay: {
    flex: 1,
    justifyContent: 'flex-end',
    backgroundColor: 'rgba(15,23,42,0.35)',
  },
  chatPanel: {
    height: '78%',
    backgroundColor: '#FFFFFF',
    borderTopLeftRadius: 12,
    borderTopRightRadius: 12,
    paddingHorizontal: 16,
    paddingTop: 14,
    paddingBottom: 12,
  },
  chatHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingBottom: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
  },
  chatTitle: {fontSize: 16, fontWeight: '700', color: '#0F172A'},
  chatSubtitle: {fontSize: 11, color: '#64748B', marginTop: 2},
  chatCloseBtn: {
    width: 34,
    height: 34,
    borderRadius: 6,
    backgroundColor: '#F1F5F9',
    alignItems: 'center',
    justifyContent: 'center',
  },
  chatCloseText: {fontSize: 24, color: '#475569', lineHeight: 26},
  chatMessages: {flex: 1, paddingVertical: 12},
  chatBubble: {
    maxWidth: '86%',
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 10,
    marginBottom: 10,
  },
  chatBubbleAssistant: {alignSelf: 'flex-start', backgroundColor: '#F1F5F9'},
  chatBubbleUser: {alignSelf: 'flex-end', backgroundColor: '#0D47A1'},
  chatBubbleText: {fontSize: 13, color: '#0F172A', lineHeight: 19},
  chatBubbleTextUser: {color: '#FFFFFF'},
  chatInputRow: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: 8,
    borderTopWidth: 1,
    borderTopColor: '#E2E8F0',
    paddingTop: 10,
  },
  chatInput: {
    flex: 1,
    minHeight: 42,
    maxHeight: 100,
    borderWidth: 1,
    borderColor: '#CBD5E1',
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 9,
    fontSize: 13,
    color: '#0F172A',
    backgroundColor: '#FFFFFF',
  },
  chatSendBtn: {
    height: 42,
    borderRadius: 8,
    paddingHorizontal: 16,
    backgroundColor: '#0D47A1',
    alignItems: 'center',
    justifyContent: 'center',
  },
  chatSendBtnDisabled: {backgroundColor: '#CBD5E1'},
  chatSendText: {fontSize: 13, color: '#FFFFFF', fontWeight: '700'},
});

export default LessonDetailScreen;
